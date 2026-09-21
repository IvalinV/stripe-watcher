<?php

declare(strict_types=1);

use StripeWatcher\StripeWatcher\StripeWatcherServiceProvider;

it('merges the package dashboard config', function () {
    expect(config('stripe-watcher.enabled'))->toBeFalse()
        ->and(config('stripe-watcher.route_prefix'))->toBe('stripe-watcher')
        ->and(config('stripe-watcher.middleware'))->toBe(['web', 'auth'])
        ->and(config('stripe-watcher.authorization_ability'))->toBe('viewStripeWatcher');
});

it('does not register dashboard routes when disabled', function () {
    expect(app('router')->getRoutes()->getByName('stripe-watcher.dashboard'))->toBeNull();
});

it('registers the dashboard route with configured middleware when enabled', function () {
    config([
        'stripe-watcher.enabled' => true,
        'stripe-watcher.route_prefix' => 'internal/stripe-events',
        'stripe-watcher.middleware' => ['web', 'auth'],
        'stripe-watcher.authorization_ability' => 'inspectStripeEvents',
    ]);

    require __DIR__.'/../../routes/stripe-watcher.php';

    $route = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'internal/stripe-events');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('internal/stripe-events')
        ->and($route->middleware())->toBe(['web', 'auth', 'can:inspectStripeEvents']);
});

it('preserves default redaction keys when configuration is partially overridden', function () {
    config(['stripe-watcher.redaction.keys' => ['private_value']]);

    (new StripeWatcherServiceProvider($this->app))->register();

    expect(config('stripe-watcher.redaction.keys'))
        ->toContain('private_value')
        ->toContain('client_secret');
});
