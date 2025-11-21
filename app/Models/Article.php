<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $primaryKey = 'article_id';

    protected $fillable = [
         'judul',
        'konten',
        'penulis_id',
        'kategori',
        'tanggal',
        'gambar'
    ];

    public function penulis()
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }
}
