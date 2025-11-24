<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = [
            [
                'user_id' => 2, // John Doe
                'balance' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3, // Jane Smith
                'balance' => 300000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 1, // Admin (optional)
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('wallets')->insert($wallets);
    }
}
