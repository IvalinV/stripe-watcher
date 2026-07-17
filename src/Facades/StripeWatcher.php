<?php

declare(strict_types=1);

namespace StripeWatcher\StripeWatcher\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \StripeWatcher\StripeWatcher\StripeWatcher
 */
class StripeWatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \StripeWatcher\StripeWatcher\StripeWatcher::class;
    }
}
