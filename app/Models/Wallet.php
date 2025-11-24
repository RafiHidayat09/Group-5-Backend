<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Methods
    public function hasSufficientBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function deductBalance($amount)
    {
        if (!$this->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }

        $this->balance -= $amount;
        $this->save();
    }

    public function addBalance($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function getFormattedBalance()
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    public function topUp($amount, $description = 'Top Up')
    {
        $this->addBalance($amount);

        $this->transactions()->create([
            'type' => 'topup',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed'
        ]);
    }

    public function pay($amount, $description = 'Payment')
    {
        $this->deductBalance($amount);

        $this->transactions()->create([
            'type' => 'payment',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed'
        ]);
    }
}
