<?php

namespace Lorisleiva\Actions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\ClosureCommand;
use Lorisleiva\Actions\Repositories\ActionRepository;

class ActionManager
{
    /**
     * @var ActionRepository
     */
    private $repository;

    /**
     * ActionManager constructor.
     * @param ActionRepository $repository
     */
    public function __construct(ActionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerActionCommands(): void
    {
        foreach ($this->repository->all() as $class) {
            try {
                /** @var Action $action */
                $action = app()->make($class);
                if (!$action->canRunAsCommand()) {
                    continue;
                }
                \Artisan::command($action->getCommandSignature(), function () use ($action) {
                    /** @var ClosureCommand $command */
                    $command = $this;
                    $input = $command->input;
                    $output = $command->getOutput();
                    try {
                        $result = $action->runAsCommand($input);
                        $formatted = $action->formatResultForConsole($result);
                        $output->write($formatted);
                        return 0;
                    } catch (\Exception $e) {
                        return 1;
                    }
                })->describe($action->getCommandDescription());
            } catch (BindingResolutionException $e) {
            }
        }
    }

    public function getRepository()
    {
        return $this->repository;
    }
}
