<?php

declare(strict_types=1);

return [

    'enabled' => false,

    'route_prefix' => 'stripe-watcher',

    'middleware' => [
        'web',
        'auth',
    ],

    'authorization_ability' => 'viewStripeWatcher',

    'capture' => [
        'signature_attribute' => 'stripe_signature_verified',
    ],

    'storage' => [
        'table' => 'stripe_watcher_webhooks',
    ],

    'retention' => [
        'days' => 30,
    ],

    'redaction' => [
        'enabled' => true,
        'replacement' => '[REDACTED]',
        'keys' => [
            'password',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'client_secret',
            'signing_secret',
            'webhook_secret',
            'authorization',
            'cookie',
            'set-cookie',
            'stripe-signature',
        ],
        'headers' => [
            'authorization',
            'cookie',
            'set-cookie',
            'stripe-signature',
        ],
    ],

];
