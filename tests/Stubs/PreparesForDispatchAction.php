<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\PreparesForDispatch;
use Lorisleiva\Actions\Concerns\AsJob;

class PreparesForDispatchAction implements PreparesForDispatch
{
    use AsJob;

    /** @var array<int, int|null> */
    public static array $preparedWith;

    public function handle(int $id = 1): void
    {
        //
    }

    public function prepareForDispatch(?int $id = null): bool
    {
        static::$preparedWith[] = $id;

        return $id !== 0;
    }
}
