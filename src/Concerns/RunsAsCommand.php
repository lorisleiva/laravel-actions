<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Console\Parser;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

trait RunsAsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $commandSignature = '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $commandDescription = '';

    public function runAsCommand(InputInterface $input)
    {
        $this->runningAs = 'command';
        $attributes = $this->getAttributesFromCommandInput($input);
        return $this->fill($attributes)->run();
    }

    /**
     * Transforms CLI input into Action attributes
     * @param InputInterface $input
     * @return array
     */
    public function getAttributesFromCommandInput(InputInterface $input): array
    {
        return array_merge($input->getArguments(), $input->getOptions());
    }

    public function getCommandDescription(): string
    {
        return $this->commandDescription;
    }

    public function getInputDefinition(): InputDefinition
    {
        [$name, $arguments, $options] = Parser::parse($this->commandSignature);
        $definition = new InputDefinition();
        $definition->setArguments($arguments);
        $definition->setOptions($options);
        return $definition;
    }

    public function canRunAsCommand(): bool
    {
        return $this->getCommandSignature() !== '';
    }

    public function getCommandSignature(): string
    {
        return $this->commandSignature;
    }

    public function formatResultForConsole($result)
    {
        $dumper = new CliDumper();
        $cloner = new VarCloner();
        if ($result instanceof \Illuminate\Contracts\Support\Arrayable) {
            $result = $result->toArray();
        }
        return $dumper->dump($cloner->cloneVar($result), true);
    }
}
