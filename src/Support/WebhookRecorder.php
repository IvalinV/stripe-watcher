<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookRecorder
{
    public function start(Request $request): StripeWebhook
    {
        $body = $request->getContent();
        $payload = $this->payload($body);
        $signatureVerified = $this->signatureStatus($request);

        if ($signatureVerified === null) {
            try {
                Log::warning('Stripe Watcher captured a webhook without a signature verification result.', [
                    'signature_attribute' => (string) config(
                        'stripe-watcher.capture.signature_attribute',
                        'stripe_signature_verified',
                    ),
                ]);
            } catch (Throwable) {
                // Logging must not prevent the webhook from being recorded.
            }
        }

        return StripeWebhook::create([
            'event_id' => $this->stringValue($payload['id'] ?? null),
            'event_type' => $this->stringValue($payload['type'] ?? null),
            'api_version' => $this->stringValue($payload['api_version'] ?? null),
            'livemode' => is_bool($payload['livemode'] ?? null) ? $payload['livemode'] : null,
            'status' => 'received',
            'request_method' => $request->method(),
            'request_url' => $request->url(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_content_type' => $request->header('Content-Type'),
            'request_headers' => $request->headers->all(),
            'request_body' => $body,
            'request_payload' => $payload,
            'signature_verified' => $signatureVerified,
            'started_at' => now(),
        ]);
    }

    public function complete(StripeWebhook $webhook, Response $response, int $durationMs): void
    {
        $body = $response->getContent();

        $webhook->forceFill([
            'status' => 'completed',
            'response_status' => $response->getStatusCode(),
            'response_headers' => $response->headers->all(),
            'response_body' => is_string($body) ? $body : null,
            'duration_ms' => $durationMs,
            'completed_at' => now(),
        ])->save();
    }

    public function fail(StripeWebhook $webhook, Throwable $exception, int $durationMs): void
    {
        $webhook->forceFill([
            'status' => 'failed',
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
            'duration_ms' => $durationMs,
            'failed_at' => now(),
        ])->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payload(string $body): ?array
    {
        if ($body === '') {
            return null;
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    private function signatureStatus(Request $request): ?bool
    {
        $value = $request->attributes->get((string) config(
            'stripe-watcher.capture.signature_attribute',
            'stripe_signature_verified',
        ));

        return is_bool($value) ? $value : null;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
