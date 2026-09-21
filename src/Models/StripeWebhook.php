<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Models;

use Illuminate\Database\Eloquent\Model;
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
        $this->attributes['request_headers'] = $this->redactionEnabled()
            ? json_encode(app(WebhookRedactor::class)->headers($value ?? []))
            : null;
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function setRequestPayloadAttribute(?array $value): void
    {
        $this->attributes['request_payload'] = $this->redactionEnabled()
            ? json_encode(app(WebhookRedactor::class)->payload($value ?? []))
            : null;
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function setResponseHeadersAttribute(?array $value): void
    {
        $this->attributes['response_headers'] = $this->redactionEnabled()
            ? json_encode(app(WebhookRedactor::class)->headers($value ?? []))
            : null;
    }

    public function setRequestBodyAttribute(?string $value): void
    {
        $this->attributes['request_body'] = app(WebhookRedactor::class)->body($value);
    }

    public function setResponseBodyAttribute(?string $value): void
    {
        $this->attributes['response_body'] = app(WebhookRedactor::class)->body($value);
    }

    public function setExceptionTraceAttribute(?string $value): void
    {
        $this->attributes['exception_trace'] = app(WebhookRedactor::class)->trace($value);
    }

    private function redactionEnabled(): bool
    {
        return (bool) config('stripe-watcher.redaction.enabled', true);
    }
}
