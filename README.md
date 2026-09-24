<div align="center">
    <h1>Stripe Watcher</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/ivalin-venkov/stripe-watcher"><img src="https://img.shields.io/packagist/v/ivalin-venkov/stripe-watcher.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/ivalin-venkov/stripe-watcher"><img src="https://img.shields.io/packagist/php-v/ivalin-venkov/stripe-watcher.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/ivalin-venkov/stripe-watcher"><img src="https://badge.laravel.cloud/badge/ivalin-venkov/stripe-watcher?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/ivalin-venkov/stripe-watcher/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/ivalin-venkov/stripe-watcher/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/ivalin-venkov/stripe-watcher"><img src="https://img.shields.io/packagist/dt/ivalin-venkov/stripe-watcher.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Package allowing you to inspect the stripe webhooks payload and result

## Installation

You can install the package via Composer:

```bash
composer require ivalin-venkov/stripe-watcher
```

Stripe Watcher is designed to work alongside Stripe's PHP SDK. The package
does not verify webhook signatures itself; verify the request in your existing
webhook integration before the capture middleware runs. Composer will suggest
`stripe/stripe-php`, but it is not a required dependency because verification
may also be handled by Laravel Cashier or custom application code.

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="stripe-watcher"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="stripe-watcher-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="stripe-watcher-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="stripe-watcher-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="stripe-watcher-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="stripe-watcher-assets"
```

## Usage

The dashboard is disabled by default. Enable it explicitly in the published
configuration file:

```php
'enabled' => true,
```

The dashboard route is protected with Laravel authorization. Define the
configured ability in the application, for example:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewStripeWatcher', function (User $user): bool {
    return $user->is_admin;
});
```

The dashboard uses the `web`, `auth`, and `can:viewStripeWatcher` middleware by
default. These middleware, the route prefix, and the ability name can be
changed in `config/stripe-watcher.php`.

The package provides a `stripe_watcher_webhooks` table and model for webhook
capture. Publish and run the package migration when you are ready to enable
storage. Attach the capture middleware to the application's existing Stripe
webhook route; the package does not create a proxy endpoint:

```php
use StripeWatcher\StripeWatcher\Http\Middleware\CaptureWebhook;

Route::post('/stripe/webhook', WebhookController::class)
    ->middleware(CaptureWebhook::class);
```

Request and response headers and JSON payloads are redacted at the model
boundary before storage. Configure the storage table through `storage.table`
and sanitization through the `redaction` section of `config/stripe-watcher.php`.
The package migration always creates and rolls back `stripe_watcher_webhooks`;
if you configure a custom storage table, create its migration separately.
Redaction fails closed: disabling it omits bodies, headers, and payloads rather
than storing them unredacted. Invalid or non-JSON bodies are also omitted.

Request URLs are omitted to avoid persisting secrets embedded in URL paths. If
signature verification runs before the capture middleware, set the result
on the request using `capture.signature_attribute` (default:
`stripe_signature_verified`). Captured records include request metadata,
response data, exceptions, and processing duration. If the capture middleware
does not find a boolean verification result, including when the attribute is
missing or contains a non-boolean value, it records the webhook and writes
a warning to the application log so the integration can be checked.

Exception messages and traces are omitted when redaction is disabled. Known
configured sensitive values and common Stripe secret formats are omitted when
redaction is enabled.

The default retention period is 30 days. Prune old records with:

```bash
php artisan stripe-watcher:prune
php artisan stripe-watcher:prune --days=7
```

Configure the default with `retention.days`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Stripe Watcher! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Ivalin Venkov](https://github.com/ivalin-venkov)
- [All Contributors](../../contributors)

## License

Stripe Watcher is open-sourced software licensed under the [MIT license](LICENSE.md).
