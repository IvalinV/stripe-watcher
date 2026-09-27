<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        config([
            'stripe-watcher.enabled' => true,
            'stripe-watcher.middleware' => ['web'],
        ]);

        Gate::define('viewStripeWatcher', fn (): bool => true);
    }
}
