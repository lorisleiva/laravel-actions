<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Queue\Interruptible;

class InterruptibleJobDecorator extends JobDecorator implements Interruptible
{
}
