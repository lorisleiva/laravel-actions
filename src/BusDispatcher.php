<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Dispatcher as IlluminateDispatcher;

class BusDispatcher extends IlluminateDispatcher
{
    public function dispatchNow($command, $handler = null)
    {
        if (! $command instanceof Action) {
            return parent::dispatchNow($command, $handler);
        }

        $callback = function ($command) {
            return $this->container->call([$command, 'runAsJob']);
        };

        return $this->pipeline->send($command)->through($this->pipes)->then($callback);
    }
}
