<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\PreparesForDispatch;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class PreparesForDispatchConfiguringAction implements PreparesForDispatch
{
    use AsJob;

    public function handle(string $queue): void
    {
        //
    }

    public function prepareForDispatch(?JobDecorator $job = null, ?string $queue = null): void
    {
        $job?->onQueue($queue);
    }
}
