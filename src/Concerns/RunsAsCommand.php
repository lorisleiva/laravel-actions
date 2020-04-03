<?php

namespace Lorisleiva\Actions\Concerns;

use Symfony\Component\Console\Input\InputInterface;

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

    public function getCommandSignature(): string
    {
        return $this->commandSignature;
    }

    public function getCommandDescription(): string
    {
        return $this->commandDescription;
    }

    public function canRunAsCommand(): bool
    {
        return $this->getCommandSignature() !== '';
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
}
