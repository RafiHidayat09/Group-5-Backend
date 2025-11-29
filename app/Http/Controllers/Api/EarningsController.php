<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Psychologist;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EarningsController extends Controller
{
    /**
     * Get psychologist earnings summary
     */
    public function getEarnings(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();

            // Cari profil psikiater
            $psychologist = Psychologist::where('user_id', $user->id)->first();

            if (!$psychologist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Psychologist profile not found'
                ], 404);
            }

            // Total earnings dari konsultasi completed
            $totalEarnings = $psychologist->getTotalEarnings();

            // Monthly earnings
            $monthlyEarnings = $psychologist->getMonthlyEarnings();

            // Wallet balance
            $walletBalance = $psychologist->getWalletBalance();

            // Recent transactions
            $recentTransactions = WalletTransaction::where('user_id', $user->id)
                ->with('consultation')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => (int) $transaction->amount,
                        'description' => $transaction->description,
                        'status' => $transaction->status,
                        'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                        'consultation_id' => $transaction->consultation_id,
                        'is_credit' => $transaction->type === 'credit'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_earnings' => (int) $totalEarnings,
                    'monthly_earnings' => (int) $monthlyEarnings,
                    'wallet_balance' => (int) $walletBalance,
                    'recent_transactions' => $recentTransactions
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch earnings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get earnings statistics for chart
     */
    public function getEarningsChart(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();
            $psychologist = Psychologist::where('user_id', $user->id)->first();

            if (!$psychologist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Psychologist profile not found'
                ], 404);
            }

            $months = [];
            $earnings = [];

            // Get last 6 months earnings
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $month = $date->format('Y-m');
                $monthName = $date->format('M Y');

                $monthlyEarning = $psychologist->consultations()
                    ->where('status', 'completed')
                    ->whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->sum('fee');

                $months[] = $monthName;
                $earnings[] = (int) $monthlyEarning;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'months' => $months,
                    'earnings' => $earnings
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch earnings chart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
