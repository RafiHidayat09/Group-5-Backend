<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PsikologProfileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api'); // Untuk mengakses logout harus login dahulu

Route::middleware('auth:api')->group(function () {


    // Hanya psikiater yang bisa melihat semua asesmen

     Route::get('/psikolog-profile', [PsikologProfileController::class, 'me']);
      Route::get('/psikiater', [PsikologProfileController::class, 'index']);
    Route::post('/psikiater', [PsikologProfileController::class, 'store']);
    Route::get('/psikiater/{id}', [PsikologProfileController::class, 'show']);
    Route::post('/psikiater/{id}', [PsikologProfileController::class, 'update']); // pakai POST karena React FormData
    Route::delete('/psikiater/{id}', [PsikologProfileController::class, 'destroy']);

     Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::post('/articles', [ArticleController::class, 'store']);
Route::put('/articles/{id}', [ArticleController::class, 'update']);
Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

Route::get('/users', [UserController::class, 'index']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::get('/users/stats', [UserController::class, 'stats']);


});
