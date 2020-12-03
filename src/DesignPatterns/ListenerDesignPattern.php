<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Decorators\ListenerDecorator;
use Illuminate\Events\Dispatcher;

class ListenerDesignPattern extends DesignPattern
{
    public function getTrait(): string
    {
        return AsListener::class;
    }

    public function recognizeFrame(BacktraceFrame $frame): bool
    {
        return $frame->matches(Dispatcher::class, 'dispatch');
    }

    public function decorate($instance)
    {
        return app(ListenerDecorator::class, ['action' => $instance]);
    }
}
