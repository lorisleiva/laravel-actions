<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Decorators\ListenerDecorator;

class ListenerDesignPattern extends DesignPattern
{
    public function getTrait(): string
    {
        return AsListener::class;
    }

    public function recognizeFrame(BacktraceFrame $frame): bool
    {
        return $frame->matches(Dispatcher::class, 'dispatch')
            || $frame->matches(Dispatcher::class, 'handlerWantsToBeQueued')
            || $frame->matches(CallQueuedListener::class, 'handle')
            || $frame->matches(CallQueuedListener::class, 'failed');
    }

    public function decorate($instance, BacktraceFrame $frame)
    {
        return app(ListenerDecorator::class, ['action' => $instance]);
    }
}
