<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      Article::create([
             'judul' => 'Apa Itu Overthinking?',
            'konten' => 'Overthinking adalah kondisi ketika seseorang...',
            'kategori' => 'Kesehatan Mental',
            'penulis_id' => 1,
            'tanggal' => now(),
            'gambar' => 'overthinking.jpg'
        ]);
    }
}
