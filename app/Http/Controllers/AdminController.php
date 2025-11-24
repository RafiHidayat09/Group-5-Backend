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

    public function getAllPsychologists()
    {
        $psychologists = Psychologist::latest()->get();
        return response()->json(['success' => true, 'data' => $psychologists]);
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

    public function getAllTransactions()
    {
        $transactions = WalletTransaction::with('wallet.user')->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $transactions]);
    }
}
