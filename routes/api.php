<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MidtransCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ========================================
// Public Routes (No Authentication)
// ========================================

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Midtrans Payment Callback (no auth required)
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle'])
    ->name('midtrans.callback');

// ========================================
// Protected Routes (Require Authentication)
// ========================================

Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/profile', [AuthController::class, 'profile'])->name('auth.profile');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.updateProfile');
        Route::get('/wallet', [AuthController::class, 'wallet'])->name('auth.wallet');
    });
});
