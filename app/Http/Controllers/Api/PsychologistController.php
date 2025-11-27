<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Psychologist;
use App\Models\User;
use App\Models\Consultation;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PsychologistController extends Controller
{
    /**
     * Get all psychologists with filtering
     */
    public function index(Request $request)
    {
        try {
            // 1. QUERY UTAMA
            // PERBAIKAN: Hapus 'where(is_available, true)' agar yang offline tetap muncul di list
            $query = Psychologist::with('user');

            // 2. Filter by status (online/offline)
            if ($request->has('status') && in_array($request->status, ['online', 'offline'])) {
                $query->where('status', $request->status);
            }

            // 3. Filter Search (Pencarian Nama/Spesialisasi)
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('user', function($u) use ($searchTerm) {
                        $u->where('name', 'like', "%{$searchTerm}%");
                    })->orWhere('specialization', 'like', "%{$searchTerm}%");
                });
            }

            // 4. Mapping Data
            $psychologists = $query->get()->map(function($psy) {
                return [
                    'id' => $psy->user_id,
                    'psychologist_profile_id' => $psy->id,
                    'name' => $psy->user ? $psy->user->name : 'Tanpa Nama',
                    'avatar' => $psy->user && $psy->user->avatar
                        ? asset('storage/' . $psy->user->avatar)
                        : null,
                    'email' => $psy->user ? $psy->user->email : null,

                    'specialization' => $psy->specialization,
                    'status' => $psy->status,
                    'rating' => (float) $psy->rating,
                    'review_count' => (int) $psy->review_count,
                    'fee' => (int) $psy->fee, // Casting ke Int
                    'bio' => $psy->bio,
                    'education' => $psy->education,
                    'experience' => $psy->experience,
                    'specializations' => $psy->specializations,
                    'is_favorite' => false
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $psychologists,
                'count' => $psychologists->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data psikiater',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available psychologists (online only) for Chat Sidebar
     */
    public function available()
    {
        try {
            // Untuk Sidebar Chat, tetap filter available & online
            $psychologists = Psychologist::with('user')
                ->where('is_available', true)
                ->where('status', 'online')
                ->get()
                ->map(function($psy) {
                    return [
                        'id' => $psy->user_id,
                        'name' => $psy->user->name,
                        'avatar' => $psy->user && $psy->user->avatar
                            ? asset('storage/' . $psy->user->avatar)
                            : null,
                        'specialization' => $psy->specialization,
                        'status' => $psy->status,
                        'rating' => $psy->rating,
                        'review_count' => $psy->review_count,
                        'is_favorite' => false,
                        'unseen_count' => 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $psychologists,
                'count' => $psychologists->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil psikiater tersedia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get psychologist detail by USER ID
     */
    public function show($id)
    {
        try {
            $psy = Psychologist::with('user')->where('user_id', $id)->firstOrFail();

            $data = [
                'id' => $psy->user_id,
                'name' => $psy->user->name,
                'avatar' => $psy->user && $psy->user->avatar
                    ? asset('storage/' . $psy->user->avatar)
                    : null,
                'email' => $psy->user->email,
                'specialization' => $psy->specialization,
                'status' => $psy->status,
                'rating' => $psy->rating,
                'review_count' => $psy->review_count,
                'fee' => (int) $psy->fee,
                'bio' => $psy->bio,
                'education' => $psy->education,
                'experience' => $psy->experience,
                'specializations' => $psy->specializations,
                'is_available' => $psy->is_available
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Psikiater tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function consultations(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();
            $profile = Psychologist::where('user_id', $user->id)->first();

            if (!$profile) return response()->json(['success' => true, 'data' => []]);

            $query = Consultation::where('psychologist_id', $profile->id)->with('user');

            // Handle Filter Status (Active, Pending, etc)
            if ($request->has('status') && $request->status !== 'all') {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            $consultations = $query->orderBy('updated_at', 'desc')->get()->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->user->name,
                    'avatar' => $c->user->avatar ? asset('storage/'.$c->user->avatar) : null,
                    'status' => 'online', // Status user online/offline (dummy)
                    'consultation_status' => $c->status,
                    'last_message' => 'Chat...',
                    'last_message_time' => $c->updated_at,
                    'unseen_count' => 0,
                    'user_id' => $c->user_id,
                    'fee' => (int) $c->fee
                ];
            });

            return response()->json(['success' => true, 'data' => $consultations]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Psychologist Dashboard Statistics
     */
    public function wallet()
    {
        try {
            $user = auth()->guard('api')->user();

            $profile = Psychologist::where('user_id', $user->id)->first();
            if (!$profile) {
                return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
            }

            $wallet = Wallet::where('user_id', $user->id)->first();
            $currentBalance = $wallet ? $wallet->balance : 0;

            $totalEarnings = 0;
            $monthlyEarnings = 0;
            // Variable baru untuk menampung transaksi
            $recentTransactions = [];

            if ($wallet) {
                $totalEarnings = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'topup')
                    ->where('status', 'completed')
                    ->sum('amount');

                $monthlyEarnings = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'topup')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount');

                // --- TAMBAHAN: Ambil 5 Transaksi Terakhir ---
                $recentTransactions = WalletTransaction::where('wallet_id', $wallet->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function($trx) {
                        return [
                            'id' => $trx->id,
                            'description' => $trx->description,
                            'date' => $trx->created_at->format('d M Y H:i'),
                            'amount' => $trx->amount,
                            'type' => $trx->type, // topup / withdrawal / payment
                            'is_credit' => $trx->type === 'topup', // Logic warna hijau/merah
                        ];
                    });
            }

            // ... (hitungan statistik konsultasi sama)
            $consultations = Consultation::where('psychologist_id', $profile->id);
            $totalConsultations = $consultations->count();
            $completedConsultations = (clone $consultations)->where('status', 'completed')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => [
                        'name' => $user->name,
                        'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                    ],
                    // Struktur Nested yang Benar
                    'wallet' => [
                        'balance' => $currentBalance,
                    ],
                    'earnings' => [
                        'total' => $totalEarnings,
                        'monthly' => $monthlyEarnings,
                    ],
                    'recent_transactions' => $recentTransactions, // <-- Kirim ke Frontend
                    'stats' => [
                        'total_consultations' => $totalConsultations,
                        'completed_consultations' => $completedConsultations
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
