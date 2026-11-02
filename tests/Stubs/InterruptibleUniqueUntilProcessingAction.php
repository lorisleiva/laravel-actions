<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class InterruptibleUniqueUntilProcessingAction extends InterruptibleAction implements ShouldBeUniqueUntilProcessing
{
}
