<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
            'avatar' => null,
        ]);

        // 2. User Biasa (Pasien 1)
        User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('user123'),
            'role' => 'user',
            'avatar' => null,
        ]);

        // 3. User Biasa (Pasien 2)
        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => bcrypt('user123'),
            'role' => 'user',
            'avatar' => null,
        ]);
    }
}
