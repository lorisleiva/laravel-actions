<?php

namespace Lorisleiva\Actions\Tests\Fixtures\Discovery;

use Lorisleiva\Actions\Concerns\AsAction;

class ConcreteAction
{
    use AsAction;

    public function handle(): string
    {
        return 'concrete';
    }
}
