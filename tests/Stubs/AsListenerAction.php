<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Lorisleiva\Actions\Concerns\AsListener;

class AsListenerAction
{
    use AsListener;

    public static int $constructed = 0;
    public static int $handled = 0;
    public static ?int $latestResult;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle($operation, $left, $right): void
    {
        static::$handled++;
        static::$latestResult = $operation === 'addition'
            ? $left + $right
            : $left - $right;
    }

    public function asListener(OperationRequestedEvent $event): void
    {
        $this->handle($event->operation, $event->left, $event->right);
    }
}
