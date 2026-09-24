<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stripe Watcher</title>
</head>
<body>
    <main>
        <h1>Stripe Watcher</h1>
        <p>Captured webhook requests</p>

        @forelse ($webhooks as $webhook)
            <article>
                <h2>
                    <a href="{{ route('stripe-watcher.webhook', $webhook) }}">
                        {{ $webhook->event_id ?: 'Webhook #'.$webhook->id }}
                    </a>
                </h2>
                <p>{{ $webhook->event_type ?: 'Unknown event' }}</p>
                <p>Status: {{ $webhook->status }}</p>
            </article>
        @empty
            <p>No captured webhooks.</p>
        @endforelse

        {{ $webhooks->links() }}
    </main>
</body>
</html>
