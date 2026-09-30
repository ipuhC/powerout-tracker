<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate Limiter: Auth endpoints (10 attempts / min per IP)
        RateLimiter::for('auth-attempts', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Rate Limiter: Standard authenticated endpoints (60 req / min)
        RateLimiter::for('api-authenticated', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate Limiter: Public map queries (120 req / min per IP)
        RateLimiter::for('public-map', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Rate Limiter: Outage report creation (5 reports / hr per user)
        RateLimiter::for('report-creation', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
