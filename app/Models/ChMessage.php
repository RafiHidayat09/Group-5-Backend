<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChMessage extends Model
{
    protected $table = 'ch_messages';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'consultation_id',
        'from_id',
        'from_type',
        'to_id',
        'to_type',
        'body',
        'attachment',
        'seen',
        'seen_at'
    ];

    protected $casts = [
        'seen' => 'boolean',
        'seen_at' => 'datetime',
    ];

    // Auto-generate UUID saat create
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // Relasi
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
