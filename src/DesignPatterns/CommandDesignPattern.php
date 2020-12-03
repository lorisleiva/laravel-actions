<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Decorators\CommandDecorator;
use Illuminate\Console\Application;

class CommandDesignPattern extends DesignPattern
{
    public function getTrait(): string
    {
        return AsCommand::class;
    }

    public function recognizeFrame(BacktraceFrame $frame): bool
    {
        return $frame->matches(Application::class, 'resolve');
    }

    public function decorate($instance)
    {
        return app(CommandDecorator::class, ['action' => $instance]);
    }
}
