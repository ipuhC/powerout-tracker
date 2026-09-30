<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------------------
    // Authentication Endpoints (Bearer Tokens via Laravel Sanctum)
    // -------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        // Guest routes (Rate limit: 10 attempts/min per IP)
        Route::middleware('throttle:auth-attempts')->group(function () {
            Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
            Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        });

        // Authenticated routes (Sanctum Bearer Token, Rate limit: 60 req/min)
        Route::middleware(['auth:sanctum', 'throttle:api-authenticated'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        });
    });
});
