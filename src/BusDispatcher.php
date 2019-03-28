<?php

namespace LorisLeiva\Actions;

use Illuminate\Bus\Dispatcher as IlluminateDispatcher;

class BusDispatcher extends IlluminateDispatcher
{
    public function getCommandHandler($command)
    {
        if ($command instanceof Action) {
            return new class() {
                public function handle($action)
                {
                    return $action->runAsJob();
                }
            };
        }

        return parent::getCommandHandler($command);
    }
}