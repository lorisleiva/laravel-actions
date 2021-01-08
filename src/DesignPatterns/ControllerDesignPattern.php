<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Illuminate\Routing\Route;
use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Decorators\ControllerDecorator;

class ControllerDesignPattern extends DesignPattern
{
    public function getTrait(): string
    {
        return AsController::class;
    }

    public function recognizeFrame(BacktraceFrame $frame): bool
    {
        return $frame->matches(Route::class, 'getController');
    }

    public function decorate($instance, BacktraceFrame $frame)
    {
        return app(ControllerDecorator::class, [
            'action' => $instance,
            'route' => $frame->getObject(),
        ]);
    }
}
