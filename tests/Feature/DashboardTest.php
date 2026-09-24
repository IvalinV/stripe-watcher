<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;
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

it('lists captured webhooks in the protected dashboard', function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    $this->artisan('migrate:fresh')->assertSuccessful();
    config(['stripe-watcher.enabled' => true, 'stripe-watcher.middleware' => ['web']]);
    Gate::define('viewStripeWatcher', fn (mixed $user = null): bool => true);
    require __DIR__.'/../../routes/stripe-watcher.php';

    StripeWebhook::create([
        'event_id' => 'evt_123',
        'event_type' => 'payment_intent.succeeded',
        'status' => 'completed',
        'response_status' => 200,
        'started_at' => now(),
    ]);

    $this->get('/stripe-watcher')
        ->assertSuccessful()
        ->assertSee('evt_123')
        ->assertSee('payment_intent.succeeded');
});

it('shows a captured webhook detail page', function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    $this->artisan('migrate:fresh')->assertSuccessful();
    config(['stripe-watcher.enabled' => true, 'stripe-watcher.middleware' => ['web']]);
    Gate::define('viewStripeWatcher', fn (mixed $user = null): bool => true);
    require __DIR__.'/../../routes/stripe-watcher.php';

    $webhook = StripeWebhook::create([
        'event_id' => 'evt_detail',
        'event_type' => 'charge.succeeded',
        'request_method' => 'POST',
        'signature_verified' => true,
        'request_headers' => ['Content-Type' => 'application/json'],
        'request_payload' => ['amount' => 4200],
        'status' => 'completed',
        'response_status' => 200,
        'response_headers' => ['Content-Type' => 'application/json'],
        'response_body' => '{"ok":true}',
        'started_at' => now(),
    ]);

    expect($webhook->id)->not->toBeNull();

    $this->get('/stripe-watcher/'.$webhook->id)
        ->assertSuccessful()
        ->assertSee('evt_detail')
        ->assertSee('charge.succeeded')
        ->assertSee('Request URL')
        ->assertSee('Signature verified')
        ->assertSee('4200')
        ->assertSee('Content-Type');
});

it('denies dashboard access when the configured ability rejects the user', function () {
    config(['stripe-watcher.enabled' => true, 'stripe-watcher.middleware' => []]);
    Gate::define('viewStripeWatcher', fn (mixed $user = null): bool => false);
    require __DIR__.'/../../routes/stripe-watcher.php';

    $this->get('/stripe-watcher')->assertForbidden();
});
