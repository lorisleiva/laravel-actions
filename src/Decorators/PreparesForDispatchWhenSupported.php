<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Queue\PreparesForDispatch;

/*
 * Laravel only calls prepareForDispatch() on a queued command that implements
 * PreparesForDispatch, which older Laravel versions don't have. Every decorator
 * implements this interface instead, which extends Laravel's when it exists.
 */
if (interface_exists(PreparesForDispatch::class)) {
    interface PreparesForDispatchWhenSupported extends PreparesForDispatch
    {
    }
} else {
    interface PreparesForDispatchWhenSupported
    {
    }
}
