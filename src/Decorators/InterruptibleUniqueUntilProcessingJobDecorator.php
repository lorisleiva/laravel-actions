<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Queue\Interruptible;

class InterruptibleUniqueUntilProcessingJobDecorator extends UniqueUntilProcessingJobDecorator implements Interruptible
{
}
