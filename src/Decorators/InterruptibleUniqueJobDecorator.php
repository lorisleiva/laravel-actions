<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Queue\Interruptible;

class InterruptibleUniqueJobDecorator extends UniqueJobDecorator implements Interruptible
{
}
