<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Action;

trait RunsAsCommand
{
    protected $commandSignature = '';
    protected $commandDescription = '';

    public function runAsCommand(Command $command)
    {
        $this->runningAs = 'command';
        $this->fill($this->getAttributesFromCommand($command));
        $this->consoleInput($command);
        $result = $this->run();
        $this->consoleOutput($result, $command);
        return $result;
    }

    public function registerClosureCommand(): ClosureCommand
    {
        /** @var Action $action */
        $action = $this;

        $handler = function () use ($action) {
            try {
                /** @var ClosureCommand $this */
                return $action->runAsCommand($this) ?? 0;
            } catch (Exception $e) {
                return 1;
            }
        };

        return Artisan::command($this->commandSignature, $handler)
            ->describe($this->commandDescription);
    }

    public function canRunAsCommand(): bool
    {
        return $this->getCommandSignature() !== '';
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
            $command->getOutput()->writeln(var_dump($result));
        }
    }
}
