<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PsikologProfile;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PsikologProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Pastikan ada user psikolog
    $psikolog = User::where('role', 'psikiater')->first();

    if (!$psikolog) {
        $psikolog = User::create([
            'name' => 'Psikolog Sistem',
            'email' => 'psikolog@system.com',
            'password' => bcrypt('psikolog123'),
            'role' => 'psikiater',
        ]);
    }

    // Buat profil psikolog default
    PsikologProfile::create([
       'psikolog_id' => $psikolog->id,
    'spesialisasi' => 'Kesehatan Mental Umum',
    'pengalaman' => '5 tahun',
    'deskripsi' => 'Psikolog default bawaan sistem.',
    'no_str' => 'SIPP-00001', 
    'foto' => null,
    ]);
    }
}
