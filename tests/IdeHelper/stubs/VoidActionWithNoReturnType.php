<?php

namespace Lorisleiva\Actions\Tests\IdeHelper\stubs;

use Lorisleiva\Actions\Concerns\AsAction;

class VoidActionWithNoReturnType
{
    use AsAction;

    public function handle()
    {
    }
}
