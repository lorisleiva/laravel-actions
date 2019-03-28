<?php

namespace LorisLeiva\Actions;

use Illuminate\Events\Dispatcher as IlluminateDispatcher;
use Illuminate\Support\Str;

class EventDispatcher extends IlluminateDispatcher
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