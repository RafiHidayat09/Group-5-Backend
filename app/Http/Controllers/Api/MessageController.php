<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChMessage;
use App\Models\Consultation;
use App\Models\User;
use App\Models\Psychologist;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Get all messages for a consultation
     */
    public function index($consultationId)
    {
        try {
            $user = auth()->guard('api')->user();

            // Check if consultation exists and user has access
            $consultation = Consultation::where('id', $consultationId)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id) // ✅ User (pasien)
                        ->orWhereHas('psychologist', function($q) use ($user) {
                            $q->where('user_id', $user->id); // ✅ Psychologist via relasi
                        });
                })
                ->firstOrFail();

            // Get messages with pagination
            $messages = ChMessage::where('consultation_id', $consultationId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($message) use ($consultation) {
                    return $this->formatMessage($message, $consultation);
                });

            // Mark other user's messages as seen
            $this->markMessagesAsSeen($consultationId);

            return response()->json([
                'success' => true,
                'data' => $messages,
                'count' => $messages->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil pesan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a new message
     */
    public function store(Request $request, $consultationId)
    {
        $request->validate([
            'message' => 'nullable|string|max:5000',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240'
        ]);

        if (!$request->message && !$request->file('file')) {
            return response()->json(['message' => 'Pesan atau file tidak boleh kosong'], 422);
        }

        DB::beginTransaction();

        try {
            $user = auth()->guard('api')->user();

            // 1. Validasi Konsultasi
            $consultation = Consultation::with(['user', 'psychologist.user'])
                ->where('id', (int) $consultationId)
                ->where('status', 'active')
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('psychologist', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                })
                ->firstOrFail();

            // 2. Tentukan Penerima (to_id)
            $isPatient = $consultation->user_id === $user->id;
            $receiverId = $isPatient
                ? $consultation->psychologist->user_id  // Kirim ke dokter
                : $consultation->user_id;                // Kirim ke pasien

            // 3. Handle File Upload
            $attachmentPath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('attachments', $filename, 'public');
                $attachmentPath = $path;
            }

            // 4. Simpan ke Database dengan FIELD YANG BENAR
            $message = ChMessage::create([
                'consultation_id' => $consultationId,
                'from_id' => $user->id,              // ✅ Pengirim
                'from_type' => get_class($user),     // ✅ Model Class
                'to_id' => $receiverId,              // ✅ Penerima
                'to_type' => get_class($user),       // ✅ Model Class
                'body' => $request->message,         // ✅ Gunakan 'body' bukan 'message'
                'attachment' => $attachmentPath,
                'seen' => false                      // ✅ Gunakan 'seen' bukan 'is_read'
            ]);

            DB::commit();

            // 5. Response Data
            $responseData = [
                'id' => $message->id,
                'body' => $message->body,
                'attachment' => $message->attachment,
                'attachment_url' => $message->attachment ? url('storage/' . $message->attachment) : null,
                'sender_id' => $message->from_id,
                'sender_type' => $message->from_type,
                'is_sender' => true,
                'seen' => false,
                'created_at' => $message->created_at,
                'time' => $message->created_at->format('H:i'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                ]
            ];

            // 6. Trigger WebSocket (Optional)
            // broadcast(new MessageSent($responseData))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'data' => $responseData
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sesi konsultasi tidak ditemukan atau sudah berakhir.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            // Hapus file jika DB gagal
            if (isset($attachmentPath) && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Delete a message
     */
    public function destroy($id)
    {
        try {
            $user = auth()->guard('api')->user();

            $message = ChMessage::where('from_id', $user->id)
                ->where('from_type', get_class($user))
                ->findOrFail($id);

            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pesan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark message as seen
     */
    public function markAsSeen($id)
    {
        try {
            $user = auth()->guard('api')->user();

            $message = ChMessage::where('to_id', $user->id)
                ->where('to_type', get_class($user))
                ->findOrFail($id);

            $message->update([
                'seen' => true,
                'seen_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan ditandai sebagai sudah dibaca'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai pesan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all messages as seen for a consultation
     */
    public function markAllAsSeen($consultationId)
    {
        try {
            $user = auth()->guard('api')->user();

            $consultation = Consultation::where('id', $consultationId)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id) // ✅ User (pasien)
                        ->orWhereHas('psychologist', function($q) use ($user) {
                            $q->where('user_id', $user->id); // ✅ Psychologist via relasi
                        });
                })
                ->firstOrFail();

            $updated = ChMessage::where('consultation_id', $consultationId)
                ->where('to_id', $user->id)
                ->where('to_type', get_class($user))
                ->where('seen', false)
                ->update([
                    'seen' => true,
                    'seen_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} pesan ditandai sebagai sudah dibaca"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai pesan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle file upload for attachments
     */
    private function handleFileUpload($file)
    {
        $path = $file->store('attachments', 'public');

        return [
            'type' => $this->getFileType($file->getMimeType()),
            'file' => $path,
            'title' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'url' => Storage::url($path),
            'download_url' => route('api.attachments.download', ['fileName' => $path])
        ];
    }

    /**
     * Determine file type from MIME type
     */
    private function getFileType($mimeType)
    {
        if (str_contains($mimeType, 'image/')) {
            return 'image';
        } elseif (str_contains($mimeType, 'video/')) {
            return 'video';
        } elseif (str_contains($mimeType, 'audio/')) {
            return 'audio';
        } elseif ($mimeType === 'application/pdf') {
            return 'pdf';
        } else {
            return 'file';
        }
    }

    /**
     * Format message for response
     */
    private function formatMessage($message, $consultation)
    {
        $user = auth()->guard('api')->user();
        $isSender = $message->from_id === $user->id &&
                    $message->from_type === get_class($user);

        return [
            'id' => $message->id,
            'body' => $message->body,
            'attachment' => $message->attachment,
            'sender_id' => $message->from_id,
            'sender_type' => $message->from_type,
            'is_sender' => $isSender,
            'seen' => $message->seen,
            'seen_at' => $message->seen_at,
            'created_at' => $message->created_at,
            'time_ago' => $message->created_at->diffForHumans(),
            'time' => $message->created_at->format('H:i'),
            'date' => $message->created_at->format('d M Y')
        ];
    }

    /**
     * Mark messages as seen for current user
     */
    private function markMessagesAsSeen($consultationId)
    {
        $user = auth()->guard('api')->user();

        ChMessage::where('consultation_id', $consultationId)
            ->where('to_id', $user->id)
            ->where('to_type', get_class($user))
            ->where('seen', false)
            ->update([
                'seen' => true,
                'seen_at' => now()
            ]);
    }
}
