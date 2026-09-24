<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Support;

use JsonException;

class WebhookRedactor
{
    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function headers(array $headers): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->redactArray($headers, $this->configuredNames('headers'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function payload(array $payload): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->redactArray($payload, $this->configuredNames('keys'));
    }

    public function body(?string $body): ?string
    {
        if ($body === null) {
            return $body;
        }

        if (! $this->enabled()) {
            return null;
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        try {
            return json_encode($this->payload($payload), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    public function message(?string $message): ?string
    {
        if ($message === null || ! $this->enabled()) {
            return null;
        }

        if ($this->containsSecretFormat($message)) {
            return null;
        }

        $names = array_unique([
            ...$this->configuredNames('keys'),
            ...$this->configuredNames('headers'),
        ]);

        $pattern = '/(?:'.implode('|', array_map(
            static fn (string $name): string => preg_quote($name, '/'),
            $names,
        )).')\s*["\']?\s*[:=]/i';

        return preg_match($pattern, $message) === 1 ? null : $message;
    }

    public function trace(?string $trace): ?string
    {
        if ($trace === null || ! $this->enabled()) {
            return null;
        }

        if ($this->containsSecretFormat($trace)) {
            return null;
        }

        $lines = preg_split('/\R/', $trace, -1, PREG_SPLIT_NO_EMPTY);

        if ($lines === false) {
            return null;
        }

        $frames = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (! str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^(#\d+\s+.*?(?:\): |:\s+))(.*)$/', $line, $matches) === 1) {
                $call = preg_replace('/\(.*$/', '', $matches[2]);
                $line = $matches[1].($call ?? $matches[2]);
            } else {
                $line = preg_replace('/((?:->|::)[A-Za-z_][A-Za-z0-9_]*)\(.*$/', '$1', $line) ?? $line;
            }

            $frames[] = $line;

            if (count($frames) === 20) {
                break;
            }
        }

        return $frames === [] ? null : implode(PHP_EOL, $frames);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $sensitiveNames
     * @return array<string, mixed>
     */
    private function redactArray(array $values, array $sensitiveNames): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveNames, true)) {
                $values[$key] = $this->replacement();
            } elseif (is_string($value) && $this->containsSecretFormat($value)) {
                $values[$key] = $this->replacement();
            } elseif (is_object($value)) {
                $values[$key] = $this->replacement();
            } elseif (is_array($value)) {
                $values[$key] = $this->redactArray($value, $sensitiveNames);
            }
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function configuredNames(string $key): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = require dirname(__DIR__, 2).'/config/stripe-watcher.php';

        return array_values(array_map(
            static fn (string $name): string => strtolower($name),
            array_unique([
                ...((array) ($defaults['redaction'][$key] ?? [])),
                ...((array) config('stripe-watcher.redaction.'.$key, [])),
            ]),
        ));
    }

    private function enabled(): bool
    {
        return (bool) config('stripe-watcher.redaction.enabled', true);
    }

    private function replacement(): string
    {
        return (string) config('stripe-watcher.redaction.replacement', '[REDACTED]');
    }

    private function containsSecretFormat(string $value): bool
    {
        return preg_match('/\b(?:sk|rk)_(?:test|live)_[A-Za-z0-9]+\b|\bwhsec_[A-Za-z0-9]+\b/i', $value) === 1;
    }
}
