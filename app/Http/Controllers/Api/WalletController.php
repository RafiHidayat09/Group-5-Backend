<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Get wallet balance
     */
    public function getBalance()
    {
        try {
            $user = auth()->guard('api')->user();

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $wallet->balance,
                    'formatted_balance' => 'Rp ' . number_format($wallet->balance, 0, ',', '.')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil saldo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Top up wallet
     */
    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:10000|max:10000000' // min 10k, max 10jt
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->guard('api')->user();

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // Create transaction record
            $transaction = $wallet->transactions()->create([
                'type' => 'topup',
                'amount' => $request->amount,
                'description' => 'Top Up Wallet',
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
                'metadata' => [
                    'top_up_amount' => $request->amount,
                    'user_id' => $user->id
                ]
            ]);

            // Untuk simulasi, langsung approve top up
            // Dalam production, ini akan redirect ke payment gateway
            $wallet->addBalance($request->amount);
            $transaction->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Top up berhasil',
                'data' => [
                    'new_balance' => $wallet->balance,
                    'formatted_new_balance' => 'Rp ' . number_format($wallet->balance, 0, ',', '.'),
                    'transaction_id' => $transaction->id,
                    'amount_added' => $request->amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan top up',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();

            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'count' => 0
                ]);
            }

            $transactions = $wallet->transactions()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'status' => $transaction->status,
                        'payment_method' => $transaction->payment_method,
                        'date' => $transaction->created_at->format('d M Y H:i'),
                        'formatted_amount' => ($transaction->type === 'topup' ? '+' : '-') . ' Rp ' . number_format($transaction->amount, 0, ',', '.'),
                        'color_class' => $transaction->type === 'topup' ? 'text-green-600' : 'text-red-600'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'count' => $transactions->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction detail
     */
    public function transactionDetail($id)
    {
        try {
            $user = auth()->guard('api')->user();

            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet tidak ditemukan'
                ], 404);
            }

            $transaction = $wallet->transactions()->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
