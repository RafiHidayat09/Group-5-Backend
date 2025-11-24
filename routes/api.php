<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\PsychologistController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Storage;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::middleware(['auth:api'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // File Download (Siapapun yang login bisa download lampiran chat)
    Route::get('/attachments/{fileName}', function ($fileName) {
        if (!Storage::disk('public')->exists($fileName)) {
            abort(404);
        }
        $filePath = Storage::disk('public')->path($fileName);
        return response()->download($filePath);
    })->name('api.attachments.download');

    Route::middleware(['role:user'])->group(function () {
        // Wallet Action (User yang bayar)
        Route::post('/wallet/top-up', [WalletController::class, 'topUp']);

        // Consultation Action (User yang memulai)
        Route::post('/consultation/start', [ConsultationController::class, 'start']);
        Route::post('/consultation/{id}/pay', [ConsultationController::class, 'pay']);
        Route::post('/consultation/{id}/rate', [ConsultationController::class, 'rate']);

        // View Psychologist List (Biasanya user yang cari dokter)
        Route::get('/psychologists', [PsychologistController::class, 'index']);
        Route::get('/psychologists/available', [PsychologistController::class, 'available']);
        Route::get('/psychologists/{id}', [PsychologistController::class, 'show']);
    });

    Route::middleware(['role:user,psychologist'])->group(function () {

        // Wallet Read (Cek Saldo)
        // Asumsi: Psikolog juga punya wallet untuk menerima gaji/withdraw
        Route::get('/wallet/balance', [WalletController::class, 'getBalance']);
        Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
        Route::get('/wallet/transactions/{id}', [WalletController::class, 'transactionDetail']);

        // Consultation Detail & End
        Route::get('/consultation/{id}', [ConsultationController::class, 'show']);
        Route::post('/consultation/{id}/end', [ConsultationController::class, 'end']); // Bisa diakhiri kedua pihak

        // Chat / Messages (PENTING: Keduanya harus bisa kirim pesan)
        Route::get('/consultation/{consultationId}/messages', [MessageController::class, 'index']);
        Route::post('/consultation/{consultationId}/message', [MessageController::class, 'store']);
        Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
        Route::post('/messages/{id}/seen', [MessageController::class, 'markAsSeen']);
        Route::post('/consultation/{consultationId}/mark-all-seen', [MessageController::class, 'markAllAsSeen']);

        // Fitur Arsip (Tambahan dari diskusi sebelumnya)
        Route::post('/consultation/{id}/archive', [ConsultationController::class, 'archive']);
        Route::post('/consultation/{id}/unarchive', [ConsultationController::class, 'unarchive']);
    });

    Route::middleware(['role:admin'])->prefix('admin')->group(function () {

        // Dashboard & Stats
        Route::get('/dashboard', [AdminController::class, 'index']); // Statistik total user, omzet, dll

        // Kelola Users (Pasien)
        Route::get('/users', [AdminController::class, 'getAllUsers']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Kelola Psikolog (Verifikasi)
        Route::get('/psychologists', [AdminController::class, 'getAllPsychologists']);
        Route::post('/psychologists/{id}/approve', [AdminController::class, 'approvePsychologist']); // Ubah status jadi active/online
        Route::post('/psychologists/{id}/reject', [AdminController::class, 'rejectPsychologist']);

        // Kelola Keuangan (Lihat semua transaksi masuk)
        Route::get('/transactions', [AdminController::class, 'getAllTransactions']);

        // Kelola Withdrawal (Pencairan dana psikolog)
        Route::get('/withdrawals', [AdminController::class, 'getWithdrawalRequests']);
        Route::post('/withdrawals/{id}/approve', [AdminController::class, 'approveWithdrawal']);
    });

});
