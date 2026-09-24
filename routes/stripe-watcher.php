<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StripeWatcher\StripeWatcher\Http\Controllers\DashboardController;

Route::middleware([
    ...config('stripe-watcher.middleware'),
    'can:'.config('stripe-watcher.authorization_ability'),
])
    ->prefix(config('stripe-watcher.route_prefix'))
    ->name('stripe-watcher.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/{webhook}', [DashboardController::class, 'show'])->name('webhook');
    });
