<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Psychologist;
use Illuminate\Support\Facades\DB;

class PsychologistSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Dr. Sarah Wijaya, M.Psi., Psikolog',
                'email' => 'sarah@example.com',
                'password' => 'sarah123',
                'avatar' => 'users-avatar/Dr.SarahWijaya.jpg',
                'specialization' => 'Psikolog Klinis Dewasa',
                'bio' => 'Berpengalaman lebih dari 8 tahun menangani kasus depresi, kecemasan, dan trauma. Pendekatan yang hangat dan berpusat pada klien.',
                'education' => 'S2 Psikologi Profesi UI',
                'experience' => '8 Tahun',
                'fee' => 250000,
                'rating' => 4.9,
                'review_count' => 124,
                'status' => 'online',
                'specializations' => ['Depresi', 'Kecemasan', 'Trauma', 'Relationship'],
                'is_available' => true,
            ],
            [
                'name' => 'Budi Santoso, S.Psi., M.Psi.',
                'email' => 'budi@example.com',
                'password' => 'budi123',
                'avatar' => 'users-avatar/BudiSantoso.jpg',
                'specialization' => 'Psikolog Anak & Remaja',
                'bio' => 'Fokus pada tumbuh kembang anak, pola asuh, dan masalah emosional pada remaja. Menggunakan metode terapi bermain.',
                'education' => 'S2 Psikologi Perkembangan UGM',
                'experience' => '5 Tahun',
                'fee' => 180000,
                'rating' => 4.7,
                'review_count' => 89,
                'status' => 'online',
                'specializations' => ['Parenting', 'Adiksi Gadget', 'Bullying', 'Minat Bakat'],
                'is_available' => true,
            ],
            [
                'name' => 'Dr. Linda Kusuma, Sp.KJ',
                'email' => 'linda@example.com',
                'password' => 'linda123',
                'avatar' => 'users-avatar/Dr.LindaKusuma.jpg',
                'specialization' => 'Psikiater',
                'bio' => 'Dokter spesialis kedokteran jiwa dengan keahlian dalam manajemen obat dan psikoterapi untuk gangguan mental berat.',
                'education' => 'Spesialis Kedokteran Jiwa UNAIR',
                'experience' => '12 Tahun',
                'fee' => 350000,
                'rating' => 5.0,
                'review_count' => 210,
                'status' => 'offline',
                'specializations' => ['Bipolar', 'Skizofrenia', 'Insomnia', 'OCD'],
                'is_available' => false,
            ],
            [
                'name' => 'Andi Pratama, M.Psi.',
                'email' => 'andi@example.com',
                'password' => 'andi123',
                'avatar' => 'users-avatar/AndiPratama.jpg',
                'specialization' => 'Psikolog Industri & Organisasi',
                'bio' => 'Membantu profesional muda mengatasi burnout, stress kerja, dan perencanaan karir.',
                'education' => 'S2 Psikologi UNPAD',
                'experience' => '4 Tahun',
                'fee' => 150000,
                'rating' => 4.5,
                'review_count' => 45,
                'status' => 'online',
                'specializations' => ['Burnout', 'Karir', 'Motivasi', 'Produktivitas'],
                'is_available' => true,
            ],
            [
                'name' => 'Jessica Tan, M.Psi.',
                'email' => 'jessica@example.com',
                'password' => 'jessica123',
                'avatar' => 'users-avatar/JessicaTan.jpg',
                'specialization' => 'Psikolog Pernikahan',
                'bio' => 'Ahli dalam konseling pasangan dan keluarga. Membantu memperbaiki komunikasi dan keharmonisan rumah tangga.',
                'education' => 'S2 Profesi Psikologi Atma Jaya',
                'experience' => '6 Tahun',
                'fee' => 220000,
                'rating' => 4.8,
                'review_count' => 150,
                'status' => 'offline',
                'specializations' => ['Pasutri', 'Perselingkuhan', 'Komunikasi', 'Pre-marital'],
                'is_available' => true,
            ],
        ];

        foreach ($data as $item) {
            // 1. Buat User Akun Dulu (Enkripsi Password disini)
            $user = User::create([
                'name' => $item['name'],
                'email' => $item['email'],
                'password' => bcrypt($item['password']),
                'role' => 'psychologist',
                'avatar' => $item['avatar'],
            ]);

            // 2. Buat Profil Psikolog (Link ke User ID tadi)
            Psychologist::create([
                'user_id' => $user->id,
                'specialization' => $item['specialization'],
                'bio' => $item['bio'],
                'education' => $item['education'],
                'experience' => $item['experience'],
                'fee' => $item['fee'],
                'rating' => $item['rating'],
                'review_count' => $item['review_count'],
                'status' => $item['status'],
                'specializations' => $item['specializations'],
                'is_available' => $item['is_available'],
            ]);
        }
    }
}
