<?php

declare(strict_types=1);

use StripeWatcher\StripeWatcher\Support\WebhookRedactor;

it('redacts sensitive keys recursively from payloads', function () {
    $payload = app(WebhookRedactor::class)->payload([
        'id' => 'evt_123',
        'client_secret' => 'secret-value',
        'nested' => [
            'api_key' => 'api-value',
            'safe' => 'visible',
        ],
    ]);

    expect($payload)->toBe([
        'id' => 'evt_123',
        'client_secret' => '[REDACTED]',
        'nested' => [
            'api_key' => '[REDACTED]',
            'safe' => 'visible',
        ],
    ]);
});

it('redacts nested objects instead of serializing unknown values', function () {
    $payload = app(WebhookRedactor::class)->payload([
        'metadata' => new class implements JsonSerializable
        {
            public function jsonSerialize(): array
            {
                return ['client_secret' => 'secret-value'];
            }
        },
    ]);

    expect($payload)->toBe(['metadata' => '[REDACTED]']);
});

it('redacts sensitive headers case insensitively', function () {
    $headers = app(WebhookRedactor::class)->headers([
        'Authorization' => 'Bearer secret',
        'Stripe-Signature' => 't=123,v1=secret',
        'Content-Type' => 'application/json',
    ]);

    expect($headers)->toBe([
        'Authorization' => '[REDACTED]',
        'Stripe-Signature' => '[REDACTED]',
        'Content-Type' => 'application/json',
    ]);
});

it('redacts nested header values', function () {
    expect(app(WebhookRedactor::class)->headers([
        'metadata' => ['Authorization' => 'Bearer secret'],
    ]))->toBe([
        'metadata' => ['Authorization' => '[REDACTED]'],
    ]);
});

it('redacts header-like keys from payloads', function () {
    expect(app(WebhookRedactor::class)->payload([
        'authorization' => 'Bearer secret',
        'stripe-signature' => 'signature-secret',
    ]))->toBe([
        'authorization' => '[REDACTED]',
        'stripe-signature' => '[REDACTED]',
    ]);
});

it('redacts and encodes valid JSON bodies', function () {
    $body = app(WebhookRedactor::class)->body('{"client_secret":"secret-value","ok":true}');

    expect(json_decode($body, true))->toBe([
        'client_secret' => '[REDACTED]',
        'ok' => true,
    ]);
});

it('omits invalid JSON bodies', function () {
    expect(app(WebhookRedactor::class)->body('not-json'))->toBeNull();
});

it('uses configured replacement and keys', function () {
    config([
        'stripe-watcher.redaction.replacement' => '***',
        'stripe-watcher.redaction.keys' => ['private_value'],
    ]);

    expect(app(WebhookRedactor::class)->payload([
        'private_value' => 'hidden',
        'client_secret' => 'secret',
    ]))->toBe([
        'private_value' => '***',
        'client_secret' => '***',
    ]);

    /** @var array<string, mixed> $defaults */
    $defaults = require dirname(__DIR__, 2).'/config/stripe-watcher.php';

    config([
        'stripe-watcher.redaction.replacement' => '[REDACTED]',
        'stripe-watcher.redaction.keys' => $defaults['redaction']['keys'],
    ]);
});

it('omits bodies when redaction is disabled', function () {
    config(['stripe-watcher.redaction.enabled' => false]);

    expect(app(WebhookRedactor::class)->body('{"client_secret":"secret-value"}'))->toBeNull()
        ->and(app(WebhookRedactor::class)->headers(['Authorization' => 'secret']))->toBe([])
        ->and(app(WebhookRedactor::class)->payload(['client_secret' => 'secret']))->toBe([]);
});

it('sanitizes trace frames without a closing parenthesis delimiter', function () {
    expect(app(WebhookRedactor::class)->trace("#0 /app/file.php: Handler->run('secret-value')"))
        ->toBe('#0 /app/file.php: Handler->run');
});

it('removes trace arguments containing a colon and space', function () {
    expect(app(WebhookRedactor::class)->trace("#0 /app/file.php: Handler->run('secret: value')"))
        ->toBe('#0 /app/file.php: Handler->run')
        ->not->toContain('secret');
});
