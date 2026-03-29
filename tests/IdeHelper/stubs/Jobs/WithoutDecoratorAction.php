<?php

namespace Lorisleiva\Actions\Tests\IdeHelper\stubs\Jobs;

use Lorisleiva\Actions\Concerns\AsJob;

class WithoutDecoratorAction
{
    use AsJob;

    public function handle()
    {
    }
}
