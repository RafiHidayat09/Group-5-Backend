<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'consultation_id',
        'type',
        'amount',
        'description',
        'payment_method',
        'status',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array'
    ];

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    // Scopes
    public function scopeTopUp($query)
    {
        return $query->where('type', 'topup');
    }

    public function scopePayment($query)
    {
        return $query->where('type', 'payment');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function getFormattedAmount()
    {
        $sign = $this->type === 'topup' ? '+' : '-';
        return $sign . ' Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getColorClass()
    {
        return $this->type === 'topup' ? 'text-green-600' : 'text-red-600';
    }
}
