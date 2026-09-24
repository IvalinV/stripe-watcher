<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Console\Commands;

use Illuminate\Console\Command;
use StripeWatcher\StripeWatcher\Models\StripeWebhook;

class PruneWebhooksCommand extends Command
{
    protected $signature = 'stripe-watcher:prune {--days= : Override the configured retention period}';

    protected $description = 'Delete Stripe webhook records older than the retention period.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('stripe-watcher.retention.days', 30));

        if ($days < 1) {
            $this->error('The retention period must be at least one day.');

            return self::FAILURE;
        }

        $deleted = StripeWebhook::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->line("Pruned {$deleted} webhook record(s).");

        return self::SUCCESS;
    }
}
