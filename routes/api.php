<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PsikologProfileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api'); // Untuk mengakses logout harus login dahulu

Route::middleware('auth:api')->group(function () {

    // Menyimpan hasil asesmen
    Route::post('/quiz-results', [QuizResultController::class, 'store']);
      // Lihat quiz user sendiri
    Route::get('/quiz-results', [QuizResultController::class, 'userResults']);

    // Hanya psikiater yang bisa melihat semua asesmen
    Route::middleware('role:psikiater')->get('/psikolog/quiz-results', [QuizResultController::class, 'index']);
     Route::get('/psikolog-profile', [PsikologProfileController::class, 'me']);

});
