<?php

declare(strict_types=1);

use StripeWatcher\StripeWatcher\Models\StripeWebhook;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    $this->artisan('migrate:fresh')->assertSuccessful();
});

it('prunes webhook records older than the configured retention period', function () {
    config(['stripe-watcher.retention.days' => 7]);

    $old = StripeWebhook::create([
        'event_id' => 'evt_old',
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);
    $recent = StripeWebhook::create([
        'event_id' => 'evt_recent',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $this->artisan('stripe-watcher:prune')
        ->expectsOutput('Pruned 1 webhook record(s).')
        ->assertSuccessful();

    expect(StripeWebhook::find($old->id))->toBeNull()
        ->and(StripeWebhook::find($recent->id))->not->toBeNull();
});

it('accepts a retention period override', function () {
    StripeWebhook::create([
        'event_id' => 'evt_old',
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(4),
    ]);

    $this->artisan('stripe-watcher:prune', ['--days' => 3])
        ->expectsOutput('Pruned 1 webhook record(s).')
        ->assertSuccessful();

    expect(StripeWebhook::query()->count())->toBe(0);
});

it('rejects invalid retention periods', function () {
    $this->artisan('stripe-watcher:prune', ['--days' => 0])
        ->expectsOutput('The retention period must be at least one day.')
        ->assertFailed();
});
