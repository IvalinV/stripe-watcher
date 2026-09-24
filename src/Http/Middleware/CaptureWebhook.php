<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;
use StripeWatcher\StripeWatcher\Support\WebhookRecorder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CaptureWebhook
{
    public function __construct(private readonly WebhookRecorder $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        $webhook = null;
        $startedAt = hrtime(true);

        try {
            $webhook = $this->recorder->start($request);
        } catch (Throwable $recordingException) {
            report($recordingException);
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            if ($webhook instanceof StripeWebhook) {
                try {
                    $this->recorder->fail($webhook, $exception, $this->duration($startedAt));
                } catch (Throwable $recordingException) {
                    report($recordingException);
                }
            }

            throw $exception;
        }

        if ($webhook instanceof StripeWebhook) {
            try {
                $this->recorder->complete($webhook, $response, $this->duration($startedAt));
            } catch (Throwable $recordingException) {
                report($recordingException);
            }
        }

        return $response;
    }

    private function duration(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
