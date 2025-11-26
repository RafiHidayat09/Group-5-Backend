<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        $transactions = [
            // Top up John Doe
            [
                'wallet_id' => 2, // John's wallet
                'consultation_id' => null,
                'type' => 'topup',
                'amount' => 500000,
                'description' => 'Top up via Bank Transfer',
                'status' => 'completed',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            // Top up Jane Smith
            [
                'wallet_id' => 3, // Jane's wallet
                'consultation_id' => null,
                'type' => 'topup',
                'amount' => 300000,
                'description' => 'Top up via Credit Card',
                'status' => 'completed',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            // Payment untuk consultation #1 (John -> Dr. Sarah)
            [
                'wallet_id' => 2, // John's wallet
                'consultation_id' => 1,
                'type' => 'payment',
                'amount' => 250000,
                'description' => 'Konsultasi dengan Dr. Sarah Wijaya',
                'status' => 'completed',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ];

        DB::table('wallet_transactions')->insert($transactions);
    }
}
