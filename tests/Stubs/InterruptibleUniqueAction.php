<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\ShouldBeUnique;

class InterruptibleUniqueAction extends InterruptibleAction implements ShouldBeUnique
{
}
