<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Psychologist;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    /**
     * Helper function to get full avatar URL
     */
    private function getAvatarUrl($path)
    {
        if (!$path) return null;

        // Jika sudah full URL (http/https), langsung return
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Jika path relatif, tambahkan asset/storage
        return asset('storage/' . $path);
    }

    /**
     * Start a new consultation
     */
    public function start(Request $request)
    {
        // Validasi: Pastikan ID yang dikirim adalah ID USER yang valid
        $request->validate([
            'psychologist_id' => 'required|exists:users,id'
        ]);

        DB::beginTransaction();

        try {
            $patient = auth()->guard('api')->user(); // User yang login (Pasien)

            // 1. Cari Profil Psikolog berdasarkan User ID yang dikirim
            $psychologistProfile = Psychologist::where('user_id', $request->psychologist_id)->first();

            if (!$psychologistProfile) {
                return response()->json(['message' => 'User ini bukan psikolog'], 404);
            }

            // Cek: Jangan konsultasi dengan diri sendiri
            if ($patient->id === $psychologistProfile->user_id) {
                return response()->json(['message' => 'Tidak bisa konsultasi dengan diri sendiri'], 400);
            }

            // 2. Cek Ketersediaan (Online/Available)
            if (!$psychologistProfile->is_available || $psychologistProfile->status !== 'online') {
                return response()->json([
                    'success' => false,
                    'message' => 'Psikiater sedang offline atau tidak tersedia.'
                ], 400);
            }

            // 3. Cek Konsultasi Aktif (Pending/Active)
            $activeConsultation = Consultation::where('user_id', $patient->id)
                ->where('psychologist_id', $psychologistProfile->id)
                ->whereIn('status', ['pending', 'active'])
                ->first();

            if ($activeConsultation) {
                return response()->json([
                    'success' => true,
                    'consultation_id' => $activeConsultation->id,
                    'requires_payment' => $activeConsultation->status === 'pending',
                    'message' => 'Melanjutkan sesi konsultasi yang ada'
                ]);
            }

            // 4. Buat Konsultasi Baru
            $consultation = Consultation::create([
                'user_id' => $patient->id,
                'psychologist_id' => $psychologistProfile->id,
                'fee' => $psychologistProfile->fee,
                'status' => 'pending'
            ]);

            // 5. Cek Saldo & Auto Pay
            $wallet = Wallet::where('user_id', $patient->id)->first();
            $requiresPayment = true;

            // Ambil nama dokter untuk riwayat transaksi
            $doctorName = $psychologistProfile->user->name ?? 'Psikiater';

            if ($wallet && $wallet->balance >= $psychologistProfile->fee) {
                // Bayar otomatis
                $wallet->balance -= $psychologistProfile->fee;
                $wallet->save();

                // Catat Transaksi Keluar (User)
                $wallet->transactions()->create([
                    'type' => 'payment',
                    'amount' => $psychologistProfile->fee,
                    'description' => 'Konsultasi dengan ' . $doctorName,
                    'status' => 'completed'
                ]);

                // Update Status Konsultasi
                $consultation->update([
                    'status' => 'active',
                    'started_at' => now(),
                    'payment_status' => 'paid'
                ]);

                $requiresPayment = false;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'consultation_id' => $consultation->id,
                'requires_payment' => $requiresPayment,
                'fee' => $psychologistProfile->fee,
                'wallet_balance' => $wallet?->balance ?? 0,
                'message' => $requiresPayment ? 'Silakan lakukan pembayaran' : 'Konsultasi dimulai'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai konsultasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start consultation session (for psychologist)
     */
    public function startSession($id)
    {
        DB::beginTransaction();
        try {
            $psychologist = auth()->guard('api')->user();

            // Cek apakah user adalah psikolog
            $psychologistProfile = Psychologist::where('user_id', $psychologist->id)->first();

            if (!$psychologistProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda bukan psikolog'
                ], 403);
            }

            $consultation = Consultation::where('psychologist_id', $psychologist->id)
                ->where('id', $id)
                ->where('status', 'pending')
                ->firstOrFail();

            $consultation->update([
                'status' => 'active',
                'started_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Konsultasi berhasil dimulai',
                'data' => [
                    'id' => $consultation->id,
                    'status' => $consultation->status,
                    'started_at' => $consultation->started_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai sesi konsultasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * End consultation session
     */
    public function endSession($id)
    {
        DB::beginTransaction();
        try {
            $currentUser = auth()->guard('api')->user();

            $consultation = Consultation::where('id', $id)
                ->where(function($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id)
                          ->orWhereHas('psychologist', function($q) use ($currentUser) {
                              $q->where('user_id', $currentUser->id);
                          });
                })
                ->where('status', 'active')
                ->firstOrFail();

            $consultation->update([
                'status' => 'ended',
                'ended_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Konsultasi berhasil diakhiri',
                'data' => [
                    'id' => $consultation->id,
                    'status' => $consultation->status,
                    'ended_at' => $consultation->ended_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengakhiri konsultasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consultation detail
     */
    public function show($id)
    {
        try {
            $currentUser = auth()->guard('api')->user();

            // Eager Load User Pasien & User Psikolog
            $consultation = Consultation::with(['psychologist.user', 'user'])
                ->where('id', $id)
                // Validasi Hak Akses: Harus Pasien ATAU Dokternya
                ->where(function($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id)
                          ->orWhereHas('psychologist', function($q) use ($currentUser) {
                              $q->where('user_id', $currentUser->id);
                          });
                })
                ->firstOrFail();

            $psyProfile = $consultation->psychologist;
            $psyUser = $psyProfile->user;

            $data = [
                'consultation_id' => $consultation->id,
                'status' => $consultation->status,
                'fee' => $consultation->fee,
                'started_at' => $consultation->started_at,
                'ended_at' => $consultation->ended_at,
                'rating' => $consultation->rating,
                'review' => $consultation->review,
                'rated' => $consultation->rated,
                'requires_payment' => $consultation->status === 'pending',

                // ✅ Data Dokter Lengkap dengan Avatar Full URL
                'psychologist' => [
                    'id' => $psyUser->id,
                    'profile_id' => $psyProfile->id,
                    'name' => $psyUser->name,
                    'avatar' => $this->getAvatarUrl($psyUser->avatar),
                    'specialization' => $psyProfile->specialization,
                    'status' => $psyProfile->status,
                    'rating' => $psyProfile->rating,
                    'review_count' => $psyProfile->review_count,
                ],

                // ✅ Data Pasien dengan Avatar Full URL
                'user' => [
                    'id' => $consultation->user->id,
                    'name' => $consultation->user->name,
                    'avatar' => $this->getAvatarUrl($consultation->user->avatar),
                    'email' => $consultation->user->email,
                ]
            ];

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Konsultasi tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Pay for consultation (Manual Trigger)
     */
    public function pay(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->guard('api')->user();

            $consultation = Consultation::where('user_id', $user->id)
                ->where('id', $id)
                ->where('status', 'pending')
                ->firstOrFail();

            $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

            if ($wallet->balance < $consultation->fee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak cukup'
                ], 400);
            }

            // Nama Dokter
            $doctorName = $consultation->psychologist->user->name ?? 'Psikiater';

            // Potong Saldo
            $wallet->balance -= $consultation->fee;
            $wallet->save();

            // Catat Transaksi
            $wallet->transactions()->create([
                'type' => 'payment',
                'amount' => $consultation->fee,
                'description' => 'Pembayaran konsultasi dengan ' . $doctorName,
                'status' => 'completed'
            ]);

            // Update Konsultasi
            $consultation->update([
                'status' => 'active',
                'started_at' => now(),
                'payment_status' => 'paid'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil',
                'data' => [
                    'consultation_id' => $consultation->id,
                    'status' => $consultation->status,
                    'wallet_balance' => $wallet->balance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rate consultation
     */
    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $user = auth()->guard('api')->user();

            $consultation = Consultation::where('user_id', $user->id)
                ->where('id', $id)
                ->where('status', 'ended')
                ->where('rated', false)
                ->firstOrFail();

            // Update Konsultasi
            $consultation->update([
                'rating' => $request->rating,
                'review' => $request->review,
                'rated' => true
            ]);

            // Hitung Rata-rata Rating Dokter
            $psychologist = $consultation->psychologist;

            // Formula rata-rata baru
            $oldTotal = $psychologist->rating * $psychologist->review_count;
            $newCount = $psychologist->review_count + 1;
            $newRating = ($oldTotal + $request->rating) / $newCount;

            $psychologist->update([
                'rating' => round($newRating, 1),
                'review_count' => $newCount
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih atas penilaian Anda',
                'data' => [
                    'rating' => $request->rating,
                    'review' => $request->review
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive consultation
     */
    public function archive($id)
    {
        try {
            $currentUser = auth()->guard('api')->user();

            $consultation = Consultation::where('id', $id)
                ->where(function($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id)
                          ->orWhereHas('psychologist', function($q) use ($currentUser) {
                              $q->where('user_id', $currentUser->id);
                          });
                })
                ->firstOrFail();

            $consultation->update(['is_archived' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Konsultasi berhasil diarsipkan'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengarsipkan konsultasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unarchive consultation
     */
    public function unarchive($id)
    {
        try {
            $currentUser = auth()->guard('api')->user();

            $consultation = Consultation::where('id', $id)
                ->where(function($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id)
                          ->orWhereHas('psychologist', function($q) use ($currentUser) {
                              $q->where('user_id', $currentUser->id);
                          });
                })
                ->firstOrFail();

            $consultation->update(['is_archived' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Konsultasi berhasil dikeluarkan dari arsip'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeluarkan konsultasi dari arsip',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's consultations (for both user and psychologist)
     */
    public function userConsultations(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();
            $filter = $request->get('filter', 'all');

            // Tentukan apakah user adalah psikolog atau pasien
            $isPsychologist = Psychologist::where('user_id', $user->id)->exists();

            if ($isPsychologist) {
                // Psikolog: lihat konsultasi dimana dia sebagai psikolog
                $query = Consultation::where('psychologist_id', $user->id)
                    ->with('user:id,name,email,avatar');
            } else {
                // User biasa: lihat konsultasi dimana dia sebagai pasien
                $query = Consultation::where('user_id', $user->id)
                    ->with('psychologist.user:id,name,email,avatar');
            }

            // Filter by status
            if ($filter !== 'all') {
                $query->where('status', $filter);
            }

            $consultations = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function($consultation) use ($isPsychologist) {
                    if ($isPsychologist) {
                        // Untuk psikolog: tampilkan data pasien
                        return [
                            'id' => $consultation->id,
                            'user_name' => $consultation->user->name,
                            'user_email' => $consultation->user->email,
                            'user_avatar' => $this->getAvatarUrl($consultation->user->avatar),
                            'status' => $consultation->status,
                            'fee' => $consultation->fee,
                            'rating' => $consultation->rating,
                            'review' => $consultation->review,
                            'created_at' => $consultation->created_at,
                            'started_at' => $consultation->started_at,
                            'ended_at' => $consultation->ended_at,
                        ];
                    } else {
                        // Untuk user: tampilkan data psikolog
                        return [
                            'id' => $consultation->id,
                            'psychologist_name' => $consultation->psychologist->user->name,
                            'psychologist_avatar' => $this->getAvatarUrl($consultation->psychologist->user->avatar),
                            'psychologist_specialization' => $consultation->psychologist->specialization,
                            'status' => $consultation->status,
                            'fee' => $consultation->fee,
                            'rating' => $consultation->rating,
                            'review' => $consultation->review,
                            'created_at' => $consultation->created_at,
                            'started_at' => $consultation->started_at,
                            'ended_at' => $consultation->ended_at,
                        ];
                    }
                });

            return response()->json([
                'success' => true,
                'data' => $consultations,
                'role' => $isPsychologist ? 'psychologist' : 'user'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data konsultasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
