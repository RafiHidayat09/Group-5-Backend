<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChMessage;
use App\Models\Consultation;
use App\Models\User;
use App\Models\Psychologist;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Find message with consultation relationship
            $message = ChMessage::with('consultation')->findOrFail($id);

            // Check authorization - apakah user adalah peserta dalam konsultasi ini?
            $isAuthorized = false;

            if ($message->consultation) {
                if ($user instanceof \App\Models\User) {
                    // User biasa - cek apakah dia yang konsultasi
                    $isAuthorized = $message->consultation->user_id == $user->id;
                } elseif ($user instanceof \App\Models\Psychologist) {
                    // Psychologist - cek apakah dia yang menangani
                    $isAuthorized = $message->consultation->psychologist_id == $user->id;
                }
            }

            // Tambahan: cek apakah user adalah pengirim pesan
            if (!$isAuthorized) {
                $isAuthorized = ($message->from_id == $user->id && $message->from_type == get_class($user));
            }

            if (!$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menghapus pesan ini'
                ], 403);
            }

            // Store data sebelum delete untuk broadcast
            $consultationId = $message->consultation_id;
            $messageId = $message->id;

            // Soft delete atau hard delete
            $message->delete();

            // Broadcast deletion event
            if ($consultationId) {
                broadcast(new MessageDeleted($consultationId, $messageId))->toOthers();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dihapus'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->guard('api')->id(),
                'user_type' => get_class(auth()->guard('api')->user()),
                'message_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pesan',
                'error' => config('app.debug') ? $e->getMessage() : null
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
        try {
            // Generate safe filename
            $originalName = $file->getClientOriginalName();
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $fileName = time() . '_' . $safeName;

            // Store file
            $path = $file->storeAs('attachments', $fileName, 'public');

            // Debug info
            Log::info('File uploaded', [
                'original' => $originalName,
                'stored_as' => $fileName,
                'path' => $path,
                'full_path' => storage_path('app/public/' . $path),
                'exists' => Storage::disk('public')->exists($path)
            ]);

            return [
                'type' => $this->getFileType($file->getMimeType()),
                'file' => $fileName,
                'title' => $originalName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'url' => Storage::url($path),
                'download_url' => url('/api/attachments/download/' . $fileName),
                'extension' => $file->getClientOriginalExtension(),
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Determine file type from MIME type
     */
    private function getFileType($mimeType)
    {
        // Lebih spesifik untuk file types yang umum
        $mimeType = strtolower($mimeType);

        // Images
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        // Videos
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        // Audios
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        // Documents
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }

        // Microsoft Office
        if (in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ])) {
            return 'document';
        }

        if (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])) {
            return 'spreadsheet';
        }

        if (in_array($mimeType, [
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ])) {
            return 'presentation';
        }

        // Archives
        if (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip'
        ])) {
            return 'archive';
        }

        // Text files
        if (str_starts_with($mimeType, 'text/')) {
            return 'text';
        }

        // Default
        return 'file';
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

    public function download($id)
    {
        try {
            $user = auth()->guard('api')->user();
            $message = ChMessage::where('to_id', $user->id)->where('to_type', get_class($user))->findOrFail($id);

            // Authorization check
            $consultation = $message->consultation;
            if (!$consultation) {
                abort(404, 'Consultation not found');
            }

            $isParticipant = false;
            if ($user->role === 'user') {
                $isParticipant = $consultation->user_id == $user->id;
            } elseif ($user->role === 'psychologist') {
                if ($user->psychologist) {
                    $isParticipant = $consultation->psychologist_id == $user->psychologist->id;
                }
            }

            if (!$isParticipant) {
                abort(403, 'Unauthorized');
            }

            if (!$message->attachment_path || !Storage::exists($message->attachment_path)) {
                abort(404, 'File not found');
            }

            // Get file info
            $path = $message->attachment_path;
            $originalName = $message->attachment_name ?: basename($path);
            $mimeType = Storage::mimeType($path);

            // Return file as download response
            return Storage::download($path, $originalName, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $originalName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Download failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Download failed'
            ], 500);
        }
    }
}
