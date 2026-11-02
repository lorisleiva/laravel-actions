<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\Interruptible;
use Lorisleiva\Actions\Concerns\AsJob;

class InterruptibleAction implements Interruptible
{
    use AsJob;

    /** @var array<int, mixed>|null */
    public static ?array $interruptedWith;

    public function handle(int $id = 1): void
    {
        //
    }

    public function interrupted(int $signal, ?int $id = null): void
    {
        static::$interruptedWith = [$signal, $id];
    }
}
