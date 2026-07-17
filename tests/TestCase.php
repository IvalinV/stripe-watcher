<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use StripeWatcher\StripeWatcher\StripeWatcherServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            StripeWatcherServiceProvider::class,
        ];
    }
}
