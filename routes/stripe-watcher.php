<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware([
    ...config('stripe-watcher.middleware'),
    'can:'.config('stripe-watcher.authorization_ability'),
])
    ->prefix(config('stripe-watcher.route_prefix'))
    ->name('stripe-watcher.')
    ->group(function (): void {
        Route::get('/', fn (): string => 'Stripe Watcher dashboard.')->name('dashboard');
    });
