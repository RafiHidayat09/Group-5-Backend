<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Consultation;

class WalletTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user dan data yang diperlukan
        $johnDoe = User::where('email', 'john@example.com')->first();
        $janeSmith = User::where('email', 'jane@example.com')->first();
        $drSarah = User::where('email', 'sarah@example.com')->first();

        // Jika user tidak ditemukan, gunakan user pertama yang ada
        if (!$johnDoe) $johnDoe = User::first();
        if (!$janeSmith) $janeSmith = User::skip(1)->first() ?? User::first();
        if (!$drSarah) $drSarah = User::where('role', 'psychologist')->first() ?? User::first();

        // Ambil wallet
        $johnWallet = Wallet::where('user_id', $johnDoe->id)->first();
        $janeWallet = Wallet::where('user_id', $janeSmith->id)->first();
        $sarahWallet = Wallet::where('user_id', $drSarah->id)->first();

        // Jika wallet tidak ada, buat dulu
        if (!$johnWallet) {
            $johnWallet = Wallet::create([
                'user_id' => $johnDoe->id,
                'balance' => 0,
                'currency' => 'IDR',
                'is_active' => true
            ]);
        }
        if (!$janeWallet) {
            $janeWallet = Wallet::create([
                'user_id' => $janeSmith->id,
                'balance' => 0,
                'currency' => 'IDR',
                'is_active' => true
            ]);
        }
        if (!$sarahWallet) {
            $sarahWallet = Wallet::create([
                'user_id' => $drSarah->id,
                'balance' => 0,
                'currency' => 'IDR',
                'is_active' => true
            ]);
        }

        // Ambil consultation jika ada
        $consultation1 = Consultation::first();

        $transactions = [
            // ========================
            // TOP UP TRANSACTIONS (USER)
            // ========================

            // Top up John Doe
            [
                'user_id' => $johnDoe->id,
                'wallet_id' => $johnWallet->id,
                'consultation_id' => null,
                'type' => 'topup',
                'amount' => 500000,
                'description' => 'Top up via Bank Transfer',
                'payment_method' => 'bank_transfer',
                'status' => 'completed',
                'metadata' => json_encode([
                    'bank_name' => 'BCA',
                    'account_number' => '1234567890',
                    'reference_number' => 'TOPUP-' . time()
                ]),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],

            // Top up Jane Smith
            [
                'user_id' => $janeSmith->id,
                'wallet_id' => $janeWallet->id,
                'consultation_id' => null,
                'type' => 'topup',
                'amount' => 300000,
                'description' => 'Top up via Credit Card',
                'payment_method' => 'credit_card',
                'status' => 'completed',
                'metadata' => json_encode([
                    'card_type' => 'Visa',
                    'last_four' => '4242',
                    'reference_number' => 'TOPUP-' . (time() + 1)
                ]),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],

            // ========================
            // PAYMENT TRANSACTIONS (USER → PSIKIATER)
            // ========================

            // Payment untuk consultation #1 (John -> Dr. Sarah)
            [
                'user_id' => $johnDoe->id,
                'wallet_id' => $johnWallet->id,
                'consultation_id' => $consultation1->id ?? null,
                'type' => 'payment',
                'amount' => 250000,
                'description' => 'Konsultasi dengan Dr. Sarah Wijaya',
                'payment_method' => 'wallet',
                'status' => 'completed',
                'metadata' => json_encode([
                    'service_type' => 'consultation',
                    'psychologist_id' => $drSarah->id,
                    'duration' => '60 minutes'
                ]),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

            // ========================
            // EARNINGS TRANSACTIONS (PSIKIATER MENERIMA PEMBAYARAN)
            // ========================

            // Dr. Sarah menerima pembayaran dari consultation
            [
                'user_id' => $drSarah->id,
                'wallet_id' => $sarahWallet->id,
                'consultation_id' => $consultation1->id ?? null,
                'type' => 'topup', // Untuk psikiater, ini seperti topup dari earnings
                'amount' => 225000, // 250000 - fee platform (10%)
                'description' => 'Pendapatan konsultasi dari John Doe',
                'payment_method' => 'system',
                'status' => 'completed',
                'metadata' => json_encode([
                    'source' => 'consultation',
                    'client_id' => $johnDoe->id,
                    'platform_fee' => 25000,
                    'net_amount' => 225000
                ]),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

            // ========================
            // ADDITIONAL TRANSACTIONS
            // ========================

            // Top up tambahan Jane Smith
            [
                'user_id' => $janeSmith->id,
                'wallet_id' => $janeWallet->id,
                'consultation_id' => null,
                'type' => 'topup',
                'amount' => 200000,
                'description' => 'Top up via E-Wallet',
                'payment_method' => 'gopay',
                'status' => 'completed',
                'metadata' => json_encode([
                    'ewallet_type' => 'Gopay',
                    'phone_number' => '081234567890',
                    'reference_number' => 'TOPUP-' . (time() + 2)
                ]),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],

            // Payment Jane untuk konsultasi
            [
                'user_id' => $janeSmith->id,
                'wallet_id' => $janeWallet->id,
                'consultation_id' => $consultation1->id ?? null,
                'type' => 'payment',
                'amount' => 180000,
                'description' => 'Konsultasi dengan Budi Santoso',
                'payment_method' => 'wallet',
                'status' => 'completed',
                'metadata' => json_encode([
                    'service_type' => 'consultation',
                    'psychologist_id' => User::where('email', 'budi@example.com')->first()->id ?? $drSarah->id,
                    'duration' => '45 minutes'
                ]),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],

            // ========================
            // WITHDRAWAL TRANSACTIONS (PSIKIATER)
            // ========================

            // Dr. Sarah withdraw earnings
            [
                'user_id' => $drSarah->id,
                'wallet_id' => $sarahWallet->id,
                'consultation_id' => null,
                'type' => 'withdrawal',
                'amount' => 200000,
                'description' => 'Withdrawal to Bank Account',
                'payment_method' => 'bank_transfer',
                'status' => 'completed',
                'metadata' => json_encode([
                    'bank_name' => 'BNI',
                    'account_number' => '9876543210',
                    'account_holder' => 'Dr. Sarah Wijaya'
                ]),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ];

        DB::table('wallet_transactions')->insert($transactions);

        // Update wallet balances berdasarkan transaksi
        $this->updateWalletBalances();

        $this->command->info('Wallet transactions seeded successfully!');
        $this->command->info('John Doe balance: ' . $johnWallet->fresh()->balance);
        $this->command->info('Jane Smith balance: ' . $janeWallet->fresh()->balance);
        $this->command->info('Dr. Sarah balance: ' . $sarahWallet->fresh()->balance);
    }

    /**
     * Update wallet balances based on transactions
     */
    private function updateWalletBalances()
    {
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            $balance = DB::table('wallet_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('status', 'completed')
                ->get()
                ->reduce(function ($carry, $transaction) {
                    if (in_array($transaction->type, ['topup', 'refund'])) {
                        return $carry + $transaction->amount;
                    } elseif (in_array($transaction->type, ['payment', 'withdrawal'])) {
                        return $carry - $transaction->amount;
                    }
                    return $carry;
                }, 0);

            $wallet->update(['balance' => $balance]);
        }
    }
}
