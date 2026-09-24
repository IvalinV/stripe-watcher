# Stripe Watcher Design Handoff

## Repository State

- Laravel package: `ivalin-venkov/stripe-watcher`
- Dashboard route authorization and webhook storage/redaction foundations are implemented.
- Webhook capture middleware and the dashboard UI remain deferred.
- Current base commit: `0d19458 WIP: v1`.
- The working tree contains uncommitted fixes from the code review; do not discard them.
- The fixes preserve null JSON attributes, omit failed JSON encodings, omit exception messages containing configured sensitive keys, and make migration rollback deterministic.
- The package migration always creates and rolls back `stripe_watcher_webhooks`. A custom `storage.table` requires an application-owned migration.
- Fresh validation passed: `composer test`, `composer lint:check`, and `composer analyse`.
- Current validation result: 39 Pest tests, 87 assertions, 100% type coverage.

### Current Working-Tree Changes

- `src/Models/StripeWebhook.php`: fail-closed JSON attribute encoding and exception-message redaction.
- `src/Support/WebhookRedactor.php`: sensitive exception-message detection.
- `database/migrations/2026_09_20_000001_create_stripe_watcher_webhooks_table.php`: fixed migration table name.
- `tests/Feature/WebhookRecordTest.php` and `tests/Unit/WebhookRedactorTest.php`: regression coverage for the review findings.
- `README.md`: documents custom-table migration behavior.

These changes have not been committed yet.

## Product Direction

Stripe Watcher should be similar to Laravel Telescope, focused on inspecting Stripe webhook traffic and displaying useful diagnostic information in a formatted dashboard.

The first milestone is webhook capture. Stripe API request capture is deferred for later review.

## Webhook Integration

Capture requests through middleware attached to the application's existing Stripe webhook route. Do not create a package-owned proxy endpoint for the MVP.

The intended flow is:

```text
Stripe request -> existing application route -> Stripe Watcher middleware -> application handler -> stored result -> dashboard
```

## Stored Information

Use a package-owned database table with configurable retention and pruning. Store as much diagnostic information as practical:

- Redacted request body and parsed JSON payload
- Request headers, URL, method, IP address, user agent, and content type
- Stripe event ID, event type, API version, and livemode flag
- Signature verification result
- Application response status, headers, and body
- Processing duration
- Exception class, message, and a sanitized trace summary
- Created, completed, and failed timestamps

The dashboard should provide formatted views and safe raw values. Apply targeted, configurable redaction rather than removing useful data broadly. Authorization and signature secrets must be excluded by default, and request data must never be stored blindly.

## Dashboard Safety

Dashboard access must be explicitly enabled and protected:

- Disabled by default, especially in production
- Explicit configuration/environment flag, for example `STRIPE_WATCHER_ENABLED=true`
- Configurable route prefix
- Configurable middleware stack
- Mandatory authorization
- No dashboard routes when the feature is disabled, if practical within the chosen Laravel integration

## Proposed Authorization

The current recommendation is configurable Laravel `can:` middleware, with defaults similar to:

```php
'middleware' => [
    'web',
    'auth',
    'can:viewStripeWatcher',
],
```

The consuming application defines the ability, keeping the package independent of the application's user model, roles, or permissions package. Both the middleware stack and ability name should be configurable.

Example application integration:

```php
Gate::define('viewStripeWatcher', function (User $user): bool {
    return $user->is_admin;
});
```

### Decision

Use configurable Laravel `can:` middleware. The default ability is `viewStripeWatcher`, and both the middleware stack and ability name remain configurable.

## Suggested Implementation Phases

1. Implement the capture middleware and persistence service.
2. Add dashboard controllers and Blade views.
3. Add retention/pruning support.
4. Add feature tests for capture, failures, authorization, and dashboard output.
5. Document the completed capture flow and dashboard usage.

## Next Session Checklist

1. Review the uncommitted changes and commit them if they are accepted.
2. Implement middleware attachment for an existing application-owned Stripe webhook route; do not add a package proxy endpoint.
3. Add a persistence service that records request metadata before invoking the handler and response/exception data afterward.
4. Ensure all captured bodies, headers, payloads, and exception details pass through fail-closed sanitization.
5. Add capture tests for successful responses, handler exceptions, signature status, response data, and duration.
6. Then begin the dashboard listing/detail UI and retention/pruning work.
