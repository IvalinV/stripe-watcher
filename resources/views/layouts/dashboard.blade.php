<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Stripe Watcher')</title>
    <link rel="stylesheet" href="{{ asset('vendor/stripe-watcher/stripe-watcher.css') }}">
</head>
<body>
    <div class="dashboard-shell">
        <header class="app-header">
            <a class="brand" href="{{ route('stripe-watcher.dashboard') }}">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span>Stripe Watcher</span>
            </a>
            @yield('header-action')
        </header>

        <main class="dashboard-main">
            @yield('content')
        </main>

        <footer class="app-footer">Webhook diagnostics for your Laravel application.</footer>
    </div>
</body>
</html>
