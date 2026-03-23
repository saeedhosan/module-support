<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class TestModuleProvider extends ServiceProvider
{
    public static int $registerCount = 0;

    public function register(): void
    {
        self::$registerCount++;
    }
}
