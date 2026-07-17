<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher;

use Illuminate\Support\ServiceProvider;
use StripeWatcher\StripeWatcher\Console\Commands\StripeWatcherCommand;

class StripeWatcherServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/stripe-watcher.php', 'stripe-watcher');

        $this->app->singleton(StripeWatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/stripe-watcher.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'stripe-watcher');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'stripe-watcher');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/stripe-watcher.php' => config_path('stripe-watcher.php'),
        ], ['stripe-watcher', 'stripe-watcher-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/stripe-watcher'),
        ], ['stripe-watcher', 'stripe-watcher-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/stripe-watcher'),
        ], ['stripe-watcher', 'stripe-watcher-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/stripe-watcher'),
        ], ['stripe-watcher', 'stripe-watcher-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['stripe-watcher', 'stripe-watcher-migrations']);

        $this->commands([
            StripeWatcherCommand::class,
        ]);
    }
}
