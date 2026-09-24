---
name: stripe-watcher-development
description: >
  Configure and apply the Stripe Watcher package in Laravel applications.
license: MIT
metadata:
  author: Ivalin Venkov
---

# Stripe Watcher

Use this skill when a Laravel application needs to integrate the Stripe Watcher package.

## Primary Goal

- apply the `ivalin-venkov/stripe-watcher` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target code paths where the package should be applied

### 2. Apply the package's public API

1. Publish and run the migration:

   ```bash
   php artisan vendor:publish --tag="stripe-watcher-migrations"
   php artisan migrate
   ```

2. Attach `StripeWatcher\StripeWatcher\Http\Middleware\CaptureWebhook` to
   the application's existing Stripe webhook route. Stripe Watcher does not
   provide a proxy endpoint:

   ```php
   use StripeWatcher\StripeWatcher\Http\Middleware\CaptureWebhook;

   Route::post('/stripe/webhook', WebhookController::class)
       ->middleware(CaptureWebhook::class);
   ```

   Verify the webhook with Stripe's PHP SDK, Laravel Cashier, or application
   code before this middleware runs, and set the configured
   `capture.signature_attribute` request attribute to a boolean result. The
   package suggests `stripe/stripe-php` but does not require it. If no boolean
   result is present, including when the attribute is missing or non-boolean,
   it logs a warning and continues capturing the webhook.

3. Enable and protect the dashboard in `config/stripe-watcher.php`. Define the
   configured `authorization_ability` in the application. The dashboard is
   disabled by default.

4. Run `php artisan stripe-watcher:prune` periodically. The command uses
   `retention.days` by default or accepts a `--days` override.

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

## Examples

- capture an application's Stripe webhook request and inspect it through the
  protected dashboard

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not add a package proxy route when the application already owns the Stripe webhook route
- do not enable the dashboard without configuring its authorization ability
