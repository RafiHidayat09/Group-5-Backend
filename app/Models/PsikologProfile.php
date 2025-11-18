<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PsikologProfile extends Model
{
    protected $primaryKey = 'psikolog_id';
    public $incrementing = false; // Karena primary key berasal dari user
    protected $fillable = [
        'psikolog_id',
        'no_str',
        'spesialisasi',
        'pengalaman',
        'deskripsi',
        'foto',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'psikolog_id');
    }
}
