<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Lorisleiva\Actions\Concerns\DecorateActions;

class CommandDecorator extends Command
{
    use DecorateActions;

    public function __construct($action, Container $container)
    {
        $this->setAction($action);
        $this->setContainer($container);

        $this->signature = $this->fromActionMethodOrProperty('getCommandSignature', 'commandSignature');
        $this->name = $this->fromActionMethodOrProperty('getCommandName', 'commandName');
        $this->description = $this->fromActionMethodOrProperty('getCommandDescription', 'commandDescription');
        $this->help = $this->fromActionMethodOrProperty('getCommandHelp', 'commandHelp');
        $this->hidden = $this->fromActionMethodOrProperty('isCommandHidden', 'commandHidden', false);

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
}
