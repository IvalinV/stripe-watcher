<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Webhook {{ $webhook->event_id ?: $webhook->id }}</title>
</head>
<body>
    <main>
        <p><a href="{{ route('stripe-watcher.dashboard') }}">Back to webhooks</a></p>
        <h1>{{ $webhook->event_id ?: 'Webhook #'.$webhook->id }}</h1>
        <dl>
            <dt>Event type</dt>
            <dd>{{ $webhook->event_type ?: 'Unknown event' }}</dd>
            <dt>Request URL</dt>
            <dd>{{ $webhook->request_url ?: 'Not available' }}</dd>
            <dt>Request method</dt>
            <dd>{{ $webhook->request_method ?: 'Not available' }}</dd>
            <dt>Signature verified</dt>
            <dd>{{ $webhook->signature_verified === null ? 'Unknown' : ($webhook->signature_verified ? 'Yes' : 'No') }}</dd>
            <dt>Status</dt>
            <dd>{{ $webhook->status }}</dd>
            <dt>Response status</dt>
            <dd>{{ $webhook->response_status ?: 'Not available' }}</dd>
            <dt>Duration</dt>
            <dd>{{ $webhook->duration_ms ?? 'Not available' }} ms</dd>
        </dl>

        <h2>Request payload</h2>
        <pre>{{ json_encode($webhook->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

        <h2>Request headers</h2>
        <pre>{{ json_encode($webhook->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

        <h2>Response body</h2>
        <pre>{{ $webhook->response_body ?: 'Not available' }}</pre>

        <h2>Response headers</h2>
        <pre>{{ json_encode($webhook->response_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

        @if ($webhook->exception_class)
            <h2>Exception</h2>
            <p>{{ $webhook->exception_class }}</p>
            <p>{{ $webhook->exception_message ?: 'No exception message.' }}</p>
            <pre>{{ $webhook->exception_trace ?: 'No exception trace.' }}</pre>
        @endif
    </main>
</body>
</html>
