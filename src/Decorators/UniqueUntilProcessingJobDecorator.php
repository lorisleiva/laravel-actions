<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

/**
 * Laravel only releases the unique lock before processing when the queued
 * command itself implements ShouldBeUniqueUntilProcessing. The queue sees
 * this decorator rather than the action, so it must carry the interface.
 */
class UniqueUntilProcessingJobDecorator extends UniqueJobDecorator implements ShouldBeUniqueUntilProcessing
{
}
