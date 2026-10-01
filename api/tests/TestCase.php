<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use Illuminate\Support\Facades\RateLimiter;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiters to prevent test pollution if Redis is used as cache
        RateLimiter::clear('auth-attempts:127.0.0.1');
        RateLimiter::clear('api-authenticated:127.0.0.1');
        RateLimiter::clear('public-map:127.0.0.1');
        RateLimiter::clear('report-creation:127.0.0.1');
    }
}
