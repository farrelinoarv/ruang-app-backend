<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CategoryController;
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

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

// Campaigns (Public)
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{id}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/campaigns/{id}/updates', [CampaignController::class, 'getUpdates'])->name('campaigns.updates');

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

    // Campaigns (Protected)
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/mine', [CampaignController::class, 'myIndex'])->name('campaigns.mine');
    Route::put('/campaigns/{id}', [CampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('/campaigns/{id}/updates', [CampaignController::class, 'postUpdate'])->name('campaigns.postUpdate');
});
