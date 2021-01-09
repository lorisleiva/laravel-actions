<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Decorators\CommandDecorator;

class CommandDesignPattern extends DesignPattern
{
    public function getTrait(): string
    {
        return AsCommand::class;
    }

    public function recognizeFrame(BacktraceFrame $frame): bool
    {
        return $frame->matches(Application::class, 'resolve')
            || $frame->matches(Schedule::class, 'command');
    }

    public function decorate($instance, BacktraceFrame $frame)
    {
        return app(CommandDecorator::class, ['action' => $instance]);
    }
}
