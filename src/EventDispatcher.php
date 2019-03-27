<?php

namespace App\Actions;

use App\Actions\Action;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class EventDispatcher extends Dispatcher
{
    protected function parseClassCallable($listener)
    {
        [$class, $method] = Str::parseCallback($listener, 'handle');

        if ($this->container->make($class) instanceof Action) {
            return Str::parseCallback($listener, 'runAsListener');
        }

        return [$class, $method];
    }
}