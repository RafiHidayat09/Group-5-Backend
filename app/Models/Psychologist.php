<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // Gunakan Model biasa, bukan Authenticatable

class Psychologist extends Model
{
    use HasFactory;

    // 1. Hapus data login (name, email, password, avatar)
    // 2. Tambahkan 'user_id' sebagai foreign key
    protected $fillable = [
        'user_id',
        'no_str',
        'specialization',
        'bio',
        'education',
        'experience',
        'fee',
        'rating',
        'review_count',
        'status',
        'specializations',
        'is_available'
    ];

    // Password & Remember Token dihapus karena ada di tabel users
    // protected $hidden = [];

    protected $casts = [
        'specializations' => 'array',
        'fee' => 'decimal:2',
        'rating' => 'float',
        'is_available' => 'boolean',
    ];

    // =================================================
    // RELASI (Sangat Penting)
    // =================================================

    // Hubungkan profil ini ke akun User aslinya
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consultations()
    {
        // Konsultasi biasanya terhubung ke User ID, tapi jika struktur database
        // consultation menghubungkan ke psychologist_id (ID profil), ini benar.
        // Jika consultation menghubungkan ke user_id psikolog, pakai user()->consultations()
        return $this->hasMany(Consultation::class);
    }

    // =================================================
    // METHODS
    // =================================================

    // Helper untuk mengambil Avatar dari relasi User
    public function getAvatarUrlAttribute()
    {
        // Kita ambil avatar milik User induknya
        $avatar = $this->user->avatar;

        if (!$avatar) {
            return asset('users-avatar/avatar.png');
        }

        if (str_starts_with($avatar, 'http')) {
            return $avatar;
        }

        return asset($avatar); // Path sudah lengkap di database
    }

    public function isOnline()
    {
        return $this->status === 'online';
    }

    public function isAvailable()
    {
        return $this->is_available && $this->isOnline();
    }

    public function updateRating($newRating)
    {
        $totalRating = ($this->rating * $this->review_count) + $newRating;
        $this->review_count += 1;
        $this->rating = $totalRating / $this->review_count;
        $this->save();
    }

    public function getFormattedFee()
    {
        return 'Rp ' . number_format($this->fee, 0, ',', '.');
    }

    public function getTotalEarnings()
    {
        return $this->consultations()
            ->where('status', 'completed')
            ->sum('fee') ?? 0;
    }

    public function getWalletBalance()
    {
        // Cari wallet milik user ini
        $wallet = Wallet::where('user_id', $this->user_id)->first();

        if (!$wallet) {
            return 0;
        }

        $credit = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->sum('amount') ?? 0;

        $debit = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->sum('amount') ?? 0;

        return $credit - $debit;
    }

    public function getMonthlyEarnings($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return $this->consultations()
            ->where('status', 'completed')
            ->whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->sum('fee') ?? 0;
    }
    public function addEarnings($consultationId, $amount, $description = 'Consultation fee')
    {
        // Cari wallet user ini
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $this->user_id],
            ['balance' => 0]
        );

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user_id,  // Tambahkan ini jika ada di database
            'consultation_id' => $consultationId,
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed',
            'metadata' => [
                'psychologist_id' => $this->id,
                'source' => 'consultation'
            ]
        ]);
    }
}
