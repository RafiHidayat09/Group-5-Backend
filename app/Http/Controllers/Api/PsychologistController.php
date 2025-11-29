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
use Illuminate\Support\Facades\Hash;

class PsychologistController extends Controller
{
    /**
     * Get all psychologists with optional search and status filter (PUBLIC)
     */
    public function index(Request $request)
    {
        try {
            $query = Psychologist::with('user');

            if ($request->has('status') && in_array($request->status, ['online', 'offline'])) {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('user', function($u) use ($searchTerm) {
                        $u->where('name', 'like', "%{$searchTerm}%");
                    })->orWhere('specialization', 'like', "%{$searchTerm}%");
                });
            }

            $psychologists = $query->get()->map(function($psy) {
                return [
                    'id' => $psy->user_id,
                    'psychologist_profile_id' => $psy->id,
                    'name' => $psy->user ? $psy->user->name : 'Tanpa Nama',
                    'avatar' => $psy->user && $psy->user->avatar ? asset('storage/' . $psy->user->avatar) : null,
                    'email' => $psy->user ? $psy->user->email : null,
                    'specialization' => $psy->specialization,
                    'status' => $psy->status,
                    'rating' => (float) $psy->rating,
                    'review_count' => (int) $psy->review_count,
                    'fee' => (int) $psy->fee,
                    'bio' => $psy->bio,
                    'education' => $psy->education,
                    'experience' => $psy->experience,
                    'no_str' => $psy->no_str,
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
     * Get available psychologists (online only)
     */
    public function available()
    {
        try {
            $psychologists = Psychologist::with('user')
                ->where('is_available', true)
                ->where('status', 'online')
                ->get()
                ->map(function($psy) {
                    return [
                        'id' => $psy->user_id,
                        'name' => $psy->user->name,
                        'avatar' => $psy->user && $psy->user->avatar ? asset('storage/' . $psy->user->avatar) : null,
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
     * Get psychologist detail by USER ID (PUBLIC)
     */
    public function show($id)
    {
        try {
            $psy = Psychologist::with('user')->where('user_id', $id)->firstOrFail();

            $data = [
                'id' => $psy->user_id,
                'name' => $psy->user->name,
                'avatar' => $psy->user && $psy->user->avatar ? asset('storage/' . $psy->user->avatar) : null,
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

    /**
     * Create new psychologist (ADMIN)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'no_str' => 'nullable|string',
            'specialization' => 'nullable|string',
            'education' => 'nullable|string',
            'experience' => 'nullable|string',
            'fee' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'password123'),
                'role' => 'psychologist',
            ]);

            $lastPsychologist = Psychologist::orderBy('id', 'desc')->first();
            $nextNumber = $lastPsychologist ? ($lastPsychologist->id + 1) : 1;
            $noStr = 'SIPP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            Psychologist::create([
                'user_id' => $user->id,
                'no_str' => $noStr,
                'specialization' => $request->specialization,
                'education' => $request->education,
                'experience' => $request->experience,
                'fee' => $request->fee ?? 0,
                'bio' => $request->bio,
                'status' => 'offline',
                'is_available' => true,
                'rating' => 0,
                'review_count' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Psikiater berhasil ditambahkan'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan psikiater',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update psychologist (ADMIN) - by psychologist ID
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'no_str' => 'nullable|string',
            'specialization' => 'nullable|string',
            'education' => 'nullable|string',
            'experience' => 'nullable|string',
            'fee' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
        ]);

        try {
            $psy = Psychologist::with('user')->findOrFail($id);

            DB::beginTransaction();

            $psy->update([
                'no_str' => $request->no_str,
                'specialization' => $request->specialization,
                'education' => $request->education,
                'experience' => $request->experience,
                'fee' => $request->fee ?? 0,
                'bio' => $request->bio,
            ]);

            if ($psy->user) {
                $userData = [
                    'name' => $request->name,
                ];

                // Only update email if it's different to avoid unique constraint issues
                if ($psy->user->email !== $request->email) {
                    $userData['email'] = $request->email;
                }

                $psy->user->update($userData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Psikiater berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate psikiater',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete psychologist (ADMIN) - by psychologist ID
     */
    public function destroy($id)
    {
        try {
            $psy = Psychologist::with('user')->findOrFail($id);

            DB::beginTransaction();

            $user = $psy->user;

            // Delete psychologist first (foreign key constraint)
            $psy->delete();

            // Then delete user
            if ($user) {
                $user->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Psikiater berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus psikiater',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consultations of logged-in psychologist
     */
    public function consultations(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();
            $profile = Psychologist::where('user_id', $user->id)->first();

            if (!$profile) return response()->json(['success' => true, 'data' => []]);

            $query = Consultation::where('psychologist_id', $profile->id)->with('user');

            if ($request->has('status') && $request->status !== 'all') {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            $consultations = $query->orderBy('updated_at', 'desc')->get()->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->user->name,
                    'avatar' => $c->user->avatar ? asset('storage/'.$c->user->avatar) : null,
                    'status' => 'online',
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
     * Dashboard & wallet stats
     */
    public function wallet()
    {
        try {
            $user = auth()->guard('api')->user();
            $profile = Psychologist::where('user_id', $user->id)->first();

            if (!$profile) return response()->json(['success' => false, 'message' => 'Profile not found'], 404);

            $wallet = Wallet::where('user_id', $user->id)->first();
            $currentBalance = $wallet ? $wallet->balance : 0;

            $totalEarnings = $wallet ? WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'topup')
                ->where('status', 'completed')
                ->sum('amount') : 0;

            $monthlyEarnings = $wallet ? WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'topup')
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount') : 0;

            $recentTransactions = $wallet ? WalletTransaction::where('wallet_id', $wallet->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($trx) {
                    return [
                        'id' => $trx->id,
                        'description' => $trx->description,
                        'date' => $trx->created_at->format('d M Y H:i'),
                        'amount' => $trx->amount,
                        'type' => $trx->type,
                        'is_credit' => $trx->type === 'topup',
                    ];
                }) : [];

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
                    'wallet' => ['balance' => $currentBalance],
                    'earnings' => ['total' => $totalEarnings, 'monthly' => $monthlyEarnings],
                    'recent_transactions' => $recentTransactions,
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
