<?php

namespace Lorisleiva\Actions\Tests\Fixtures\Discovery;

use Lorisleiva\Actions\Concerns\AsAction;

abstract class AbstractAction
{
    use AsAction;

    abstract public function handle(): string;
}
