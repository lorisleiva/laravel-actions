<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Queue\Interruptible;

class InterruptibleDebounceJobDecorator extends DebounceJobDecorator implements Interruptible
{
}
