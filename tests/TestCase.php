<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use StripeWatcher\StripeWatcher\StripeWatcherServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.database', ':memory:');
    }

    protected function getPackageProviders($app): array
    {
        return [
            StripeWatcherServiceProvider::class,
        ];
    }
}
