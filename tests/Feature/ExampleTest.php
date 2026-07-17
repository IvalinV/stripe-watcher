<?php

declare(strict_types=1);

use StripeWatcher\StripeWatcher\StripeWatcher;

it('resolves the singleton', function () {
    expect(app(StripeWatcher::class))->toBeInstanceOf(StripeWatcher::class);
});

it('returns the same instance from the container', function () {
    expect(app(StripeWatcher::class))->toBe(app(StripeWatcher::class));
});

it('merges the package config', function () {
    expect(config('stripe-watcher.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('stripe-watcher::messages.placeholder'))->toBe('StripeWatcher placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('stripe-watcher::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('stripe-watcher:placeholder')
        ->expectsOutputToContain('StripeWatcher placeholder command executed.')
        ->assertSuccessful();
});
