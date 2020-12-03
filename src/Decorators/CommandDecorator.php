<?php

namespace Lorisleiva\Actions\Decorators;

use Lorisleiva\Actions\Concerns\DecorateActions;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;

class CommandDecorator extends Command
{
    use DecorateActions;

    public function __construct($action, Container $container)
    {
        $this->setAction($action);
        $this->setContainer($container);

        $this->signature = $this->fromAction('getCommandSignature', 'commandSignature');
        $this->name = $this->fromAction('getCommandName', 'commandName');
        $this->description = $this->fromAction('getCommandDescription', 'commandDescription');
        $this->help = $this->fromAction('getCommandHelp', 'commandHelp');
        $this->hidden = $this->fromAction('isCommandHidden', 'commandHidden', false);

        if (! $this->signature) {
            // TODO: Proper exceptions.
            throw new \Exception(sprintf(
                'The command signature is missing from your [%s] action. Use `public $commandSignature` to set it up. ',
                get_class($this->action)
            ));
        }

        parent::__construct();
    }

    public function handle()
    {
        if ($this->hasMethod('asCommand')) {
            return $this->resolveAndCall('asCommand', ['command' => $this]);
        }

        if ($this->hasMethod('handle')) {
            return $this->resolveAndCall('handle', ['command' => $this]);
        }
    }

    protected function fromAction(string $method, string $property, $default = null)
    {
        if ($this->hasMethod($method)) {
            return $this->callMethod($method);
        }

        if ($this->hasProperty($property)) {
            return $this->getProperty($property);
        }

        return $default;
    }
}
