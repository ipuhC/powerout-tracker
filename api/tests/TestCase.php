<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Flush entire cache to ensure rate limiters (keyed by user ID or IP)
        // never leak state between test cases.
        Cache::flush();
    }
}
