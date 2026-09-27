@extends('stripe-watcher::layouts.dashboard')

@section('title', 'Webhook activity | Stripe Watcher')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Observability</p>
            <h1>Webhook activity</h1>
            <p class="lead">Inspect captured Stripe webhook requests and their application results.</p>
        </div>

        <div class="summary-card">
            <span class="summary-label">Captured</span>
            <span class="summary-value">{{ $webhooks->total() }}</span>
        </div>
    </div>

    @if ($webhooks->count() > 0)
        <div class="webhook-list">
            @foreach ($webhooks as $webhook)
                @php
                    $statusClass = match ($webhook->status) {
                        'completed', 'success' => 'status-success',
                        'failed', 'error' => 'status-error',
                        'pending', 'processing' => 'status-processing',
                        default => 'status-default',
                    };
                @endphp
                <a class="webhook-card" href="{{ route('stripe-watcher.webhook', $webhook) }}">
                    <div class="card-top">
                        <span class="event-id">{{ $webhook->event_id ?: 'Webhook #'.$webhook->id }}</span>
                        <span class="status-badge {{ $statusClass }}">{{ $webhook->status }}</span>
                    </div>
                    <p class="event-type">{{ $webhook->event_type ?: 'Unknown event' }}</p>
                    <p class="card-meta">
                        {{ $webhook->started_at?->toDateTimeString() ?: 'Time not available' }}
                        <span aria-hidden="true">&middot;</span>
                        View webhook details
                    </p>
                </a>
            @endforeach
        </div>

        <div class="pagination">{{ $webhooks->links() }}</div>
    @else
        <div class="empty-state">
            <h2>No captured webhooks</h2>
            <p>Attach the capture middleware to your Stripe webhook route to start collecting diagnostics.</p>
        </div>
    @endif
@endsection
