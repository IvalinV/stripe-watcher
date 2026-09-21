<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    $this->artisan('migrate:fresh')->assertSuccessful();
});

it('creates a webhook record with diagnostic data and casts JSON values', function () {
    $webhook = StripeWebhook::create([
        'event_id' => 'evt_123',
        'event_type' => 'payment_intent.succeeded',
        'api_version' => '2024-06-20',
        'livemode' => true,
        'request_method' => 'POST',
        'request_url' => 'https://example.test/stripe/webhook',
        'request_ip' => '192.0.2.1',
        'user_agent' => 'Stripe/1.0',
        'request_content_type' => 'application/json',
        'request_headers' => ['Content-Type' => 'application/json'],
        'request_body' => '{"id":"evt_123"}',
        'request_payload' => ['id' => 'evt_123'],
        'signature_verified' => true,
        'response_status' => 200,
        'response_headers' => ['Content-Type' => 'application/json'],
        'response_body' => '{"ok":true}',
        'duration_ms' => 42,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
        'failed_at' => now(),
    ]);

    expect($webhook->fresh())
        ->event_id->toBe('evt_123')
        ->and($webhook->request_headers)->toBe(['Content-Type' => 'application/json'])
        ->and($webhook->request_payload)->toBe(['id' => 'evt_123'])
        ->and($webhook->response_headers)->toBe(['Content-Type' => 'application/json'])
        ->and($webhook->livemode)->toBeTrue()
        ->and($webhook->response_status)->toBe(200)
        ->and($webhook->started_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($webhook->completed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($webhook->failed_at)->toBeInstanceOf(DateTimeInterface::class);
});

it('redacts sensitive values before mass assignment persists them', function () {
    $webhook = StripeWebhook::create([
        'request_headers' => ['Authorization' => 'Bearer secret'],
        'request_body' => '{"client_secret":"secret-value"}',
        'request_payload' => ['client_secret' => 'secret-value'],
        'response_headers' => ['Authorization' => 'Bearer response-secret'],
        'response_body' => '{"token":"response-secret"}',
    ]);

    expect(json_decode($webhook->request_body, true))->toBe(['client_secret' => '[REDACTED]'])
        ->and($webhook->request_headers)->toBe(['Authorization' => '[REDACTED]'])
        ->and($webhook->request_payload)->toBe(['client_secret' => '[REDACTED]'])
        ->and($webhook->response_headers)->toBe(['Authorization' => '[REDACTED]'])
        ->and(json_decode($webhook->response_body, true))->toBe(['token' => '[REDACTED]']);
});

it('omits invalid request bodies when mass assigned', function () {
    expect(StripeWebhook::create(['request_body' => 'not-json'])->request_body)->toBeNull();
});

it('does not persist bodies when redaction is disabled', function () {
    config(['stripe-watcher.redaction.enabled' => false]);

    $webhook = StripeWebhook::create([
        'request_headers' => ['Authorization' => 'secret'],
        'request_payload' => ['client_secret' => 'secret'],
        'request_body' => '{"secret":"value"}',
        'response_headers' => ['Authorization' => 'response-secret'],
        'response_body' => '{"secret":"response-value"}',
    ]);

    expect($webhook->request_headers)->toBeNull()
        ->and($webhook->request_payload)->toBeNull()
        ->and($webhook->request_body)->toBeNull()
        ->and($webhook->response_headers)->toBeNull()
        ->and($webhook->response_body)->toBeNull();

    config(['stripe-watcher.redaction.enabled' => true]);
});

it('stores only a sanitized exception trace summary', function () {
    $webhook = StripeWebhook::create([
        'exception_trace' => "#0 /app/Handler.php(12): Handler->run('secret-value')\n#1 /app/index.php(4): Handler->run()",
    ]);

    expect($webhook->exception_trace)
        ->toBe("#0 /app/Handler.php(12): Handler->run\n#1 /app/index.php(4): Handler->run")
        ->not->toContain('secret-value');
});

it('uses the configured storage table with required columns', function () {
    expect((new StripeWebhook)->getTable())->toBe('stripe_watcher_webhooks')
        ->and(Schema::hasColumns('stripe_watcher_webhooks', [
            'event_id',
            'event_type',
            'request_headers',
            'request_body',
            'request_payload',
            'response_headers',
            'response_body',
            'exception_class',
            'exception_message',
            'exception_trace',
            'started_at',
            'completed_at',
            'failed_at',
        ]))->toBeTrue();

    config(['stripe-watcher.storage.table' => 'custom_webhooks']);

    expect((new StripeWebhook)->getTable())->toBe('custom_webhooks');

    config(['stripe-watcher.storage.table' => 'stripe_watcher_webhooks']);
});
