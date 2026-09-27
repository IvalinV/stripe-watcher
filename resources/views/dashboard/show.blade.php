@extends('stripe-watcher::layouts.dashboard')

@section('title', 'Webhook '.($webhook->event_id ?: $webhook->id).' | Stripe Watcher')

@section('header-action')
    <a class="header-link" href="{{ route('stripe-watcher.dashboard') }}">All webhooks</a>
@endsection

@section('content')
    <a class="back-link" href="{{ route('stripe-watcher.dashboard') }}">&larr; Back to webhooks</a>

    <article class="detail-card">
        <div class="detail-heading">
            <div>
                <p class="eyebrow">Webhook detail</p>
                <h1>{{ $webhook->event_id ?: 'Webhook #'.$webhook->id }}</h1>
                <p class="lead">{{ $webhook->event_type ?: 'Unknown event' }}</p>
            </div>
            <span class="status-badge status-{{ $webhook->status === 'completed' ? 'success' : ($webhook->status === 'failed' ? 'error' : 'default') }}">
                {{ $webhook->status }}
            </span>
        </div>

        <dl class="detail-grid">
            <div class="detail-item">
                <dt>Request URL</dt>
                <dd>{{ $webhook->request_url ?: 'Not available' }}</dd>
            </div>
            <div class="detail-item">
                <dt>Request method</dt>
                <dd>{{ $webhook->request_method ?: 'Not available' }}</dd>
            </div>
            <div class="detail-item">
                <dt>Signature verified</dt>
                <dd>{{ $webhook->signature_verified === null ? 'Unknown' : ($webhook->signature_verified ? 'Yes' : 'No') }}</dd>
            </div>
            <div class="detail-item">
                <dt>Response status</dt>
                <dd>{{ $webhook->response_status ?: 'Not available' }}</dd>
            </div>
            <div class="detail-item">
                <dt>Duration</dt>
                <dd>{{ $webhook->duration_ms ?? 'Not available' }} ms</dd>
            </div>
            <div class="detail-item">
                <dt>Started at</dt>
                <dd>{{ $webhook->started_at?->toDateTimeString() ?: 'Not available' }}</dd>
            </div>
        </dl>

        <section class="detail-section">
            <div class="section-heading"><h2>Request payload</h2></div>
            <pre class="code-panel">{{ json_encode($webhook->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>

        <section class="detail-section">
            <div class="section-heading"><h2>Request headers</h2></div>
            <pre class="code-panel">{{ json_encode($webhook->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>

        <section class="detail-section">
            <div class="section-heading"><h2>Response body</h2></div>
            <pre class="code-panel">{{ $webhook->response_body ?: 'Not available' }}</pre>
        </section>

        <section class="detail-section">
            <div class="section-heading"><h2>Response headers</h2></div>
            <pre class="code-panel">{{ json_encode($webhook->response_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>

        @if ($webhook->exception_class)
            <section class="detail-section">
                <div class="section-heading"><h2>Exception</h2></div>
                <p><strong>{{ $webhook->exception_class }}</strong></p>
                <p>{{ $webhook->exception_message ?: 'No exception message.' }}</p>
                <pre class="code-panel">{{ $webhook->exception_trace ?: 'No exception trace.' }}</pre>
            </section>
        @endif
    </article>
@endsection
