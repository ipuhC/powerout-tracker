<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------------------
    // Public Endpoints
    // -------------------------------------------------------------------------
    Route::prefix('public')->group(function () {
        Route::get('/outages', [\App\Http\Controllers\Api\V1\PublicOutageController::class, 'index'])->name('public.outages.index');
    });

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

    // -------------------------------------------------------------------------
    // Reports Management API
    // -------------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'throttle:api-authenticated'])->group(function () {
        Route::get('/reports', [\App\Http\Controllers\Api\V1\ReportController::class, 'index'])->name('reports.index');
        
        // Custom rate limiting for report creation: 5 requests per hour (60 minutes)
        Route::post('/reports', [\App\Http\Controllers\Api\V1\ReportController::class, 'store'])
            ->middleware('throttle:5,60')
            ->name('reports.store');
            
        Route::patch('/reports/{report}', [\App\Http\Controllers\Api\V1\ReportController::class, 'update'])->name('reports.update');
        Route::post('/reports/{report}/close', [\App\Http\Controllers\Api\V1\ReportController::class, 'close'])->name('reports.close');
    });
});
