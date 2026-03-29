<?php

namespace Lorisleiva\Actions\Tests\IdeHelper\stubs;

use Lorisleiva\Actions\Concerns\AsAction;

class VoidAction
{
    use AsAction;

    public function handle(int $i): void
    {
    }
}
