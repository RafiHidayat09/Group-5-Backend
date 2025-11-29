<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Psychologist;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::where('role', 'user')->count(),
                'total_psychologists' => Psychologist::count(),
                'total_transactions' => WalletTransaction::count(),
                'pending_psychologists' => Psychologist::where('is_available', false)->count(),
            ]
        ]);
    }

    public function getAllUsers()
    {
        $users = User::where('role', 'user')->latest()->get();
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function deleteUser($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $user->delete();
        return response()->json(['success' => true, 'message' => 'User berhasil dihapus']);
    }

    /**
     * Get all psychologists for admin (with user data)
     */
    public function getAllPsychologists(Request $request)
    {
        try {
            $query = Psychologist::with('user');

            // Optional: filter by search
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $query->whereHas('user', function($u) use ($searchTerm) {
                    $u->where('name', 'like', "%{$searchTerm}%");
                });
            }

            $psychologists = $query->orderBy('no_str', 'asc')->get()->map(function($psy) {
            return [
                'id' => $psy->id,
                'user_id' => $psy->user_id,
                'name' => $psy->user ? $psy->user->name : 'Tanpa Nama',
                'email' => $psy->user ? $psy->user->email : null,
                'no_str' => $psy->no_str,
                'specialization' => $psy->specialization,
                'education' => $psy->education,
                'experience' => $psy->experience,
                'fee' => (int) $psy->fee,
                'bio' => $psy->bio,
                'rating' => (float) $psy->rating,
                'review_count' => (int) $psy->review_count,
                'status' => $psy->status,
                'is_available' => $psy->is_available,
                'user' => $psy->user ? [
                    'avatar' => $psy->user->avatar
                ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $psychologists
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single psychologist detail (for edit form)
     */
    public function getPsychologistDetail($id)
    {
        try {
            $psy = Psychologist::with('user')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $psy->id,
                    'user_id' => $psy->user_id,
                    'name' => $psy->user->name,
                    'email' => $psy->user->email,
                    'no_str' => $psy->no_str,
                    'specialization' => $psy->specialization,
                    'education' => $psy->education,
                    'experience' => $psy->experience,
                    'fee' => (int) $psy->fee,
                    'bio' => $psy->bio,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    public function approvePsychologist($id)
    {
        $psy = Psychologist::find($id);
        if (!$psy) return response()->json(['message' => 'Psychologist not found'], 404);

        $psy->update([
            'is_available' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Psikolog disetujui']);
    }

    public function rejectPsychologist($id)
    {
        $psy = Psychologist::find($id);
        if (!$psy) return response()->json(['message' => 'Psychologist not found'], 404);

        $psy->update([
            'is_available' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Psikolog ditolak']);
    }

    public function getAllTransactions()
    {
        $transactions = WalletTransaction::with('wallet.user')->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $transactions]);
    }

    public function getWithdrawalRequests()
    {
        // Implementasi sesuai kebutuhan withdrawal
        return response()->json(['success' => true, 'data' => []]);
    }

    public function approveWithdrawal($id)
    {
        // Implementasi sesuai kebutuhan withdrawal
        return response()->json(['success' => true, 'message' => 'Withdrawal approved']);
    }
}
