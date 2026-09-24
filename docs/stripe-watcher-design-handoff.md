# Stripe Watcher Design Handoff

## Repository State

- Laravel package: `ivalin-venkov/stripe-watcher`
- Dashboard route authorization, webhook storage/redaction, capture middleware, and dashboard UI are implemented.
- Retention configuration and the pruning command are implemented.
- Current base commit: `0d19458 WIP: v1`.
- The working tree contains uncommitted fixes from the code review; do not discard them.
- The fixes preserve null JSON attributes, omit failed JSON encodings, omit exception messages containing configured sensitive keys, and make migration rollback deterministic.
- The package migration always creates and rolls back `stripe_watcher_webhooks`. A custom `storage.table` requires an application-owned migration.
- Fresh validation passed: `composer test`, `composer lint:check`, and `composer analyse`.
- Current validation result: 59 Pest tests, 168 assertions, 100% type coverage.

### Current Working-Tree Changes

- `src/Models/StripeWebhook.php` and `src/Support/WebhookRedactor.php`: fail-closed content sanitization.
- `src/Support/WebhookRecorder.php` and `src/Http/Middleware/CaptureWebhook.php`: request lifecycle capture and failure isolation.
- `src/Http/Controllers/DashboardController.php`, `routes/stripe-watcher.php`, and `resources/views/dashboard/`: protected dashboard listing and detail views.
- `src/Console/Commands/PruneWebhooksCommand.php`: configurable retention pruning.
- `database/migrations/2026_09_20_000001_create_stripe_watcher_webhooks_table.php` and `2026_09_20_000002_add_created_at_index_to_stripe_watcher_webhooks_table.php`: storage schema and retention index.
- `tests/Feature/` and `tests/Unit/`: capture, dashboard, retention, migration, and redaction coverage.
- `README.md` and `resources/boost/skills/stripe-watcher-development/SKILL.md`: consumer integration documentation.

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

## Implementation Phases

1. Implement the capture middleware and persistence service. Completed.
2. Add dashboard controllers and Blade views. Completed.
3. Add retention/pruning support. Completed.
4. Add feature tests for capture, failures, authorization, and dashboard output. Completed.
5. Document the completed capture flow and dashboard usage. Completed.

## Follow-up Checklist

1. Review and commit the accepted working-tree changes.
2. Run the full Laravel 12/13, PHP, Windows, and prefer-lowest CI matrix.
3. Add an independent upgrade/rollback test for the retention index migration if migration upgrade coverage is required.
