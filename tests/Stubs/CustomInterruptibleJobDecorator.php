<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\Interruptible;

class CustomInterruptibleJobDecorator extends CustomJobDecorator implements Interruptible
{
}
