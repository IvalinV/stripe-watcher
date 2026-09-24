<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use StripeWatcher\StripeWatcher\StripeWatcher;
use StripeWatcher\StripeWatcher\Support\WebhookRecorder;
use StripeWatcher\StripeWatcher\Support\WebhookRedactor;

it('resolves the singleton', function () {
    expect(app(StripeWatcher::class))->toBeInstanceOf(StripeWatcher::class);
});

it('returns the same instance from the container', function () {
    expect(app(StripeWatcher::class))->toBe(app(StripeWatcher::class));
});

it('resolves the webhook redactor as a singleton', function () {
    expect(app(WebhookRedactor::class))->toBeInstanceOf(WebhookRedactor::class)
        ->and(app(WebhookRedactor::class))->toBe(app(WebhookRedactor::class));
});

it('resolves the webhook recorder as a singleton', function () {
    expect(app(WebhookRecorder::class))->toBeInstanceOf(WebhookRecorder::class)
        ->and(app(WebhookRecorder::class))->toBe(app(WebhookRecorder::class));
});

it('publishes the webhook migration', function () {
    expect(ServiceProvider::pathsToPublish(null, 'stripe-watcher-migrations'))
        ->toHaveKey(dirname(__DIR__, 2).'/src/../database/migrations');
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
