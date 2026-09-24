<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use StripeWatcher\StripeWatcher\Http\Middleware\CaptureWebhook;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;
use StripeWatcher\StripeWatcher\Support\WebhookRecorder;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    $this->artisan('migrate:fresh')->assertSuccessful();
});

it('records a completed webhook request and response', function () {
    $request = Request::create(
        '/stripe/webhook/secret-value?client_secret=url-secret',
        'POST',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '192.0.2.10',
            'HTTP_USER_AGENT' => 'Stripe/1.0',
            'HTTP_STRIPE_SIGNATURE' => 'signature',
        ],
        json_encode([
            'id' => 'evt_123',
            'type' => 'payment_intent.succeeded',
            'api_version' => '2024-06-20',
            'livemode' => true,
            'client_secret' => 'secret-value',
        ], JSON_THROW_ON_ERROR),
    );
    $request->attributes->set('stripe_signature_verified', true);

    $response = app(CaptureWebhook::class)->handle($request, function (Request $request): JsonResponse {
        return response()->json(['ok' => true, 'token' => 'response-secret']);
    });

    expect($response->getStatusCode())->toBe(200)
        ->and($this->app['db']->table('stripe_watcher_webhooks')->count())->toBe(1);

    $record = $this->app['db']->table('stripe_watcher_webhooks')->first();

    expect($record->event_id)->toBe('evt_123')
        ->and($record->event_type)->toBe('payment_intent.succeeded')
        ->and($record->livemode)->toBe(1)
        ->and($record->signature_verified)->toBe(1)
        ->and($record->status)->toBe('completed')
        ->and($record->response_status)->toBe(200)
        ->and($record->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($record->completed_at)->not->toBeNull()
        ->and($record->request_url)->toBeNull()
        ->and(json_decode($record->request_body, true))->toMatchArray([
            'client_secret' => '[REDACTED]',
        ])
        ->and(json_decode($record->request_payload, true))->toMatchArray([
            'client_secret' => '[REDACTED]',
        ])
        ->and(json_decode($record->request_headers, true)['stripe-signature'])->toBe('[REDACTED]')
        ->and(json_decode($record->response_body, true))->toBe([
            'ok' => true,
            'token' => '[REDACTED]',
        ]);
});

it('captures requests through an application route middleware attachment', function () {
    Route::post('/application-stripe-webhook', fn (): JsonResponse => response()->json(['ok' => true]))
        ->middleware(CaptureWebhook::class);

    $this->postJson('/application-stripe-webhook', ['id' => 'evt_route'])
        ->assertSuccessful();

    expect($this->app['db']->table('stripe_watcher_webhooks')->first()->event_id)->toBe('evt_route');
});

it('warns when a webhook has no signature verification result', function () {
    Log::spy();

    $response = app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(200);
    expect($this->app['db']->table('stripe_watcher_webhooks')->first()->signature_verified)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Stripe Watcher captured a webhook without a signature verification result.'
            && $context['signature_attribute'] === 'stripe_signature_verified',
        );
});

it('warns when the signature verification result is not boolean', function () {
    Log::spy();

    $request = Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}');
    $request->attributes->set('stripe_signature_verified', 'true');

    $response = app(CaptureWebhook::class)->handle(
        $request,
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($this->app['db']->table('stripe_watcher_webhooks')->first()->signature_verified)->toBeNull();

    Log::shouldHaveReceived('warning')->once();
});

it('does not warn when signature verification explicitly fails', function () {
    Log::spy();

    $request = Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}');
    $request->attributes->set('stripe_signature_verified', false);

    app(CaptureWebhook::class)->handle(
        $request,
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($this->app['db']->table('stripe_watcher_webhooks')->first()->signature_verified)->toBe(0);

    Log::shouldNotHaveReceived('warning');
});

it('uses a custom signature verification attribute', function () {
    config(['stripe-watcher.capture.signature_attribute' => 'webhook_verified']);
    Log::spy();

    $response = app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(200);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $context['signature_attribute'] === 'webhook_verified',
        );
});

it('continues recording when warning logging fails', function () {
    Log::shouldReceive('warning')->once()->andThrow(new RuntimeException('Logger unavailable'));

    $response = app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($this->app['db']->table('stripe_watcher_webhooks')->count())->toBe(1);
});

it('records handler exceptions and rethrows them', function () {
    $request = Request::create(
        '/stripe/webhook',
        'POST',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode(['id' => 'evt_failed'], JSON_THROW_ON_ERROR),
    );

    expect(fn () => app(CaptureWebhook::class)->handle($request, function (): never {
        throw new RuntimeException('Webhook failed');
    }))->toThrow(RuntimeException::class, 'Webhook failed');

    $record = $this->app['db']->table('stripe_watcher_webhooks')->first();

    expect($record->event_id)->toBe('evt_failed')
        ->and($record->status)->toBe('failed')
        ->and($record->exception_class)->toBe(RuntimeException::class)
        ->and($record->exception_message)->toBe('Webhook failed')
        ->and($record->failed_at)->not->toBeNull();
});

it('preserves a successful response when completion recording fails', function () {
    app()->instance(WebhookRecorder::class, new class extends WebhookRecorder
    {
        public function complete(StripeWebhook $webhook, Response $response, int $durationMs): void
        {
            throw new RuntimeException('Unable to record completion');
        }
    });

    $response = app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(200);
});

it('preserves the original exception when failure recording fails', function () {
    app()->instance(WebhookRecorder::class, new class extends WebhookRecorder
    {
        public function fail(StripeWebhook $webhook, Throwable $exception, int $durationMs): void
        {
            throw new RuntimeException('Unable to record failure');
        }
    });

    expect(fn () => app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        function (Request $request): never {
            throw new LogicException('Original webhook failure');
        },
    ))->toThrow(LogicException::class, 'Original webhook failure');
});

it('preserves a successful response when initial recording fails', function () {
    app()->instance(WebhookRecorder::class, new class extends WebhookRecorder
    {
        public function start(Request $request): StripeWebhook
        {
            throw new RuntimeException('Unable to start recording');
        }
    });

    $response = app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        fn (Request $request): Response => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(200);
});

it('preserves the original exception when initial recording fails', function () {
    app()->instance(WebhookRecorder::class, new class extends WebhookRecorder
    {
        public function start(Request $request): StripeWebhook
        {
            throw new RuntimeException('Unable to start recording');
        }
    });

    expect(fn () => app(CaptureWebhook::class)->handle(
        Request::create('/stripe/webhook', 'POST', [], [], [], [], '{}'),
        function (Request $request): never {
            throw new LogicException('Original webhook failure');
        },
    ))->toThrow(LogicException::class, 'Original webhook failure');
});
