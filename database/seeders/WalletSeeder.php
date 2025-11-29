<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $wallets = [
            [
                'user_id' => 1, // Admin
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
            // Psychologists juga butuh wallet
            [
                'user_id' => 4, // Dr. Sarah (user_id dari psychologist)
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 5, // Budi
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 6, // Dr. Linda
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 7, // Andi
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 8, // Jessica
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('wallets')->insert($wallets);
    }
}
