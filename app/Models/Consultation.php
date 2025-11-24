<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'psychologist_id',
        'status',
        'fee',
        'started_at',
        'ended_at',
        'rating',
        'review',
        'rated'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'rated' => 'boolean',
        'rating' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function psychologist()
    {
        return $this->belongsTo(Psychologist::class);
    }

    public function messages()
    {
        return $this->hasMany(ChMessage::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPsychologist($query, $psychologistId)
    {
        return $query->where('psychologist_id', $psychologistId);
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isEnded()
    {
        return $this->status === 'ended';
    }

    public function canBeRated()
    {
        return $this->isEnded() && !$this->rated;
    }

    public function getDurationInMinutes()
    {
        if (!$this->started_at || !$this->ended_at) {
            return 0;
        }

        return $this->ended_at->diffInMinutes($this->started_at);
    }

    public function markAsActive()
    {
        $this->update([
            'status' => 'active',
            'started_at' => now()
        ]);
    }

    public function markAsEnded()
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now()
        ]);
    }

    public function markAsRated($rating, $review = null)
    {
        $this->update([
            'rating' => $rating,
            'review' => $review,
            'rated' => true
        ]);
    }
}
