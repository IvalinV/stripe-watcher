<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Models;

use Illuminate\Database\Eloquent\Model;
use JsonException;
use StripeWatcher\StripeWatcher\Support\WebhookRedactor;

class StripeWebhook extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = [];

    protected $casts = [
        'livemode' => 'boolean',
        'signature_verified' => 'boolean',
        'request_headers' => 'array',
        'request_payload' => 'array',
        'response_headers' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return (string) config('stripe-watcher.storage.table', 'stripe_watcher_webhooks');
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function setRequestHeadersAttribute(?array $value): void
    {
        $this->attributes['request_headers'] = $value === null || ! $this->redactionEnabled()
            ? null
            : $this->encode(app(WebhookRedactor::class)->headers($value));
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function setRequestPayloadAttribute(?array $value): void
    {
        $this->attributes['request_payload'] = $value === null || ! $this->redactionEnabled()
            ? null
            : $this->encode(app(WebhookRedactor::class)->payload($value));
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function setResponseHeadersAttribute(?array $value): void
    {
        $this->attributes['response_headers'] = $value === null || ! $this->redactionEnabled()
            ? null
            : $this->encode(app(WebhookRedactor::class)->headers($value));
    }

    public function setRequestBodyAttribute(?string $value): void
    {
        $this->attributes['request_body'] = app(WebhookRedactor::class)->body($value);
    }

    public function setRequestUrlAttribute(?string $value): void
    {
        $this->attributes['request_url'] = null;
    }

    public function setResponseBodyAttribute(?string $value): void
    {
        $this->attributes['response_body'] = app(WebhookRedactor::class)->body($value);
    }

    public function setExceptionTraceAttribute(?string $value): void
    {
        $this->attributes['exception_trace'] = app(WebhookRedactor::class)->trace($value);
    }

    public function setExceptionMessageAttribute(?string $value): void
    {
        $this->attributes['exception_message'] = app(WebhookRedactor::class)->message($value);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encode(array $value): ?string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    private function redactionEnabled(): bool
    {
        return (bool) config('stripe-watcher.redaction.enabled', true);
    }
}
