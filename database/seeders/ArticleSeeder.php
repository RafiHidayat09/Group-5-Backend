<?php

namespace Database\Seeders;
use App\Models\Article;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'penulis_id' => 4,
            'tanggal' => now(),
            'gambar' => 'overthinking.jpg'
        ]);
    }
}
