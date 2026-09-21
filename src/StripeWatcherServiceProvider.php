<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher;

use Illuminate\Support\ServiceProvider;
use StripeWatcher\StripeWatcher\Console\Commands\StripeWatcherCommand;
use StripeWatcher\StripeWatcher\Support\WebhookRedactor;

class StripeWatcherServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $configPath = __DIR__.'/../config/stripe-watcher.php';

        $this->mergeConfigFrom($configPath, 'stripe-watcher');

        /** @var array<string, mixed> $defaults */
        $defaults = require $configPath;
        /** @var array<string, mixed> $configured */
        $configured = (array) config('stripe-watcher', []);
        $configuration = array_replace_recursive($defaults, $configured);

        $configuration['redaction']['keys'] = array_values(array_unique([
            ...$defaults['redaction']['keys'],
            ...((array) ($configured['redaction']['keys'] ?? [])),
        ]));
        $configuration['redaction']['headers'] = array_values(array_unique([
            ...$defaults['redaction']['headers'],
            ...((array) ($configured['redaction']['headers'] ?? [])),
        ]));

        config(['stripe-watcher' => $configuration]);

        $this->app->singleton(StripeWatcher::class);
        $this->app->singleton(WebhookRedactor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'stripe-watcher');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'stripe-watcher');

        if (config('stripe-watcher.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/stripe-watcher.php');
        }

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
