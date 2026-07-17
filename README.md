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

<!-- Add a basic usage example here. -->

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
