<?php

namespace Lorisleiva\Actions;

use Artisan;
use Exception;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Collection;

class ActionManager
{
    public function registerActionCommands(): void
    {
        $this->getActions()
            ->filter(static function (Action $action) {
                return $action->canRunAsCommand();
            })->each(static function (Action $action) {
                Artisan::command($action->getCommandSignature(), function () use ($action) {
                    /** @var ClosureCommand $command */
                    $command = $this;
                    $input = $command->input;
                    $output = $command->getOutput();
                    try {
                        $result = $action->runAsCommand($input);
                        $action->outputResultToConsole($result, $output);
                        return 0;
                    } catch (Exception $e) {
                        return 1;
                    }
                })->describe($action->getCommandDescription());
            });
    }

    /**
     * Get instances of all discovered Action classes
     * @return Collection
     */
    public function getActions(): Collection
    {
        return collect(); // TODO
    }
}
