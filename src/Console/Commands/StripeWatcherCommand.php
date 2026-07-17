<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Console\Commands;

use Illuminate\Console\Command;

class StripeWatcherCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'stripe-watcher:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package stripe-watcher.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('StripeWatcher placeholder command executed.');

        return self::SUCCESS;
    }
}
