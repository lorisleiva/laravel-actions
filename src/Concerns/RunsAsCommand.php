<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Action;

trait RunsAsCommand
{
    protected $commandInstance;
    protected $commandSignature = '';
    protected $commandDescription = '';

    public function runAsCommand(Command $command)
    {
        $this->runningAs = 'command';
        $this->commandInstance = $command;
        $this->fill($this->getAttributesFromCommand($command));

        return $this->run();
    }

    public function registerCommand(): ?ClosureCommand
    {
        /** @var Action $action */
        if (! ($action = $this)->canRunAsCommand()) {
            return null;
        }

        $handler = function () use ($action) {
            try {
                /** @var ClosureCommand $command */
                $command = $this;
                $result = $action->runAsCommand($command);
                return $action->consoleOutput($result, $command) ?? 0;
            } catch (Exception $e) {
                return 1;
            }
        };

        return Artisan::command($this->commandSignature, $handler)
            ->describe($this->commandDescription);
    }

    public function canRunAsCommand(): bool
    {
        return $this->commandSignature !== '';
    }

    public function getAttributesFromCommand(Command $command): array
    {
        return array_merge($command->arguments(), $command->options());
    }

    public function consoleInput(Command $command)
    {
        //
    }

    public function consoleOutput($result, Command $command)
    {
        if ($output = $command->getOutput()) {
            $command->getOutput()->write(var_export($result, true));
        }
    }

    public function getCommandInstance()
    {
        return $this->commandInstance;
    }
}
