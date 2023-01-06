<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Lorisleiva\Actions\Concerns\AsListener;

class AsListenerHandleAction
{
    use AsListener;

    public static int $constructed = 0;
    public static int $handled = 0;
    public static ?int $latestResult;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle(OperationRequestedEvent $event): void
    {
        static::$handled++;
        static::$latestResult = $event->operation === 'addition'
            ? $event->left + $event->right
            : $event->left - $event->right;
    }
}
