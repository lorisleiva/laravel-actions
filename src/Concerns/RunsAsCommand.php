<?php

namespace Lorisleiva\Actions\Concerns;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Action;

trait RunsAsCommand
{
    protected $commandInstance;
    protected static $commandSignature = '';
    protected static $commandDescription = '';

    public static function registerCommand(): ?ClosureCommand
    {
        if (! static::canRunAsCommand()) {
            return null;
        }

        $self = static::class;
        $handler = function () use ($self) {
            try {
                /** @var ClosureCommand $command */
                $command = $this;

                /** @var Action $action */
                $action = new $self;

                $result = $action->runAsCommand($command);
                return $action->consoleOutput($result, $command) ?? 0;
            } catch (Exception $e) {
                return 1;
            }
        };

        return Artisan::command(static::$commandSignature, $handler)
            ->describe(static::$commandDescription);
    }

    public static function canRunAsCommand(): bool
    {
        return static::$commandSignature !== '';
    }

    public function runAsCommand(Command $command)
    {
        $this->runningAs = 'command';
        $this->commandInstance = $command;
        $this->fill($this->getAttributesFromCommand($command));

        return $this->run();
    }

    public function getAttributesFromCommand(Command $command): array
    {
        return array_merge($command->arguments(), $command->options());
    }

    public function consoleOutput($result, Command $command)
    {
        if ($output = $command->getOutput()) {
            $command->getOutput()->write(var_export($result, true));
        }
    }

    public function getCommandInstance(): Command
    {
        return $this->commandInstance;
    }
}
