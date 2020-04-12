<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\Collection;

class ActionManager
{
    public function registerActionCommands(): void
    {
        $this->getActions()
            ->filter->canRunAsCommand()
            ->each->registerClosureCommand();
    }

    public function getActions(): Collection
    {
        return collect(); // TODO
    }
}
