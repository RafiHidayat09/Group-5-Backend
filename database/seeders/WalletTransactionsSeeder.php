<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions = [
            [
                'wallet_id' => 1, // John Doe's wallet
                'type' => 'topup',
                'amount' => 500000,
                'description' => 'Top up via Bank Transfer',
                'status' => 'completed',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'wallet_id' => 2, // Jane Smith's wallet
                'type' => 'topup',
                'amount' => 300000,
                'description' => 'Top up via Credit Card',
                'status' => 'completed',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'wallet_id' => 1,
                'type' => 'payment',
                'amount' => 150000,
                'description' => 'Konsultasi dengan Dr. Sarah Wijaya',
                'status' => 'completed',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ];

        DB::table('wallet_transactions')->insert($transactions);
    }
}
