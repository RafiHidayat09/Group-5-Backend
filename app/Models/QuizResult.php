<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
     protected $fillable = [
        'user_id','stress','kecemasan','depresi','burnout','kualitas_tidur','ai_tips'
    ];

    protected $casts = [
        'ai_tips' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
