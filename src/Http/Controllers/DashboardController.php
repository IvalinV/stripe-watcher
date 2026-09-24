<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Http\Controllers;

use Illuminate\Http\Response;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;

class DashboardController
{
    public function index(): Response
    {
        return response()->view('stripe-watcher::dashboard.index', [
            'webhooks' => StripeWebhook::query()
                ->latest('started_at')
                ->latest('id')
                ->paginate(25),
        ]);
    }

    public function show(StripeWebhook $webhook): Response
    {
        return response()->view('stripe-watcher::dashboard.show', compact('webhook'));
    }
}
