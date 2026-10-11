<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ServiceCategoryController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ==========================================
    // 1. Authentication Routes
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp'); // Convenience alias

        // Protected Auth Routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('/me', [ProfileController::class, 'show'])->name('auth.me');
        });
    });

    // ==========================================
    // 2. Protected User Profile & RBAC Routes
    // ==========================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ProfileController::class, 'show'])->name('me');
        Route::put('/me', [ProfileController::class, 'update'])->name('me.update');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // ==========================================
    // 3. Service Engine Routes (Day 6)
    // ==========================================

    // Public Service & Category Discovery Endpoints
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/service-categories', [ServiceCategoryController::class, 'index'])->name('service-categories.index');
    Route::get('/service-categories/{category}', [ServiceCategoryController::class, 'show'])->name('service-categories.show');

    // Admin & Operational Management Endpoints (Protected by Sanctum + Policies)
    Route::middleware('auth:sanctum')->group(function () {
        // Services CRUD
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        // Service Categories CRUD
        Route::post('/service-categories', [ServiceCategoryController::class, 'store'])->name('service-categories.store');
        Route::put('/service-categories/{category}', [ServiceCategoryController::class, 'update'])->name('service-categories.update');
        Route::delete('/service-categories/{category}', [ServiceCategoryController::class, 'destroy'])->name('service-categories.destroy');
    });

});
