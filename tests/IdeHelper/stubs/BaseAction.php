<?php

namespace Lorisleiva\Actions\Tests\IdeHelper\stubs;

use Lorisleiva\Actions\Concerns\AsObject;

class BaseAction
{
    use AsObject;

    public function handle(): string
    {
        return "";
    }
}
