<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Queue\Attributes\DebounceFor;

#[DebounceFor(60)]
class InterruptibleDebounceAction extends InterruptibleAction
{
}
