<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsultationsSeeder extends Seeder
{
    public function run(): void
    {
        $consultations = [
            [
                'user_id' => 2, // John Doe
                'psychologist_id' => 1, // ✅ Dr. Sarah (ID dari tabel psychologists)
                'fee' => 250000,
                'status' => 'ended',
                'started_at' => now()->subDays(2)->subHours(3),
                'ended_at' => now()->subDays(2)->subHours(2),
                'rated' => true,
                'rating' => 5,
                'review' => 'Sangat membantu dan profesional',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'user_id' => 3, // Jane Smith
                'psychologist_id' => 2, // ✅ Budi (ID dari tabel psychologists)
                'fee' => 180000,
                'status' => 'active',
                'started_at' => now()->subMinutes(30),
                'ended_at' => null,
                'rated' => false,
                'rating' => null,
                'review' => null,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'user_id' => 2, // John Doe
                'psychologist_id' => 4, // ✅ Andi (ID dari tabel psychologists)
                'fee' => 150000,
                'status' => 'pending',
                'started_at' => null,
                'ended_at' => null,
                'rated' => false,
                'rating' => null,
                'review' => null,
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ],
        ];

        DB::table('consultations')->insert($consultations);
    }
}
