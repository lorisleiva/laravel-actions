<?php

namespace Lorisleiva\Actions;

use Artisan;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\ClosureCommand;

class ActionManager
{
    /**
     * @var ActionDiscovery
     */
    private $discovery;

    /**
     * ActionManager constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->discovery = new ActionDiscovery($config);
    }

    public function registerActionCommands(): void
    {
        $this->discovery->getActions()
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
                        $formatted = $action->formatResultForConsole($result);
                        $output->write($formatted);
                        return 0;
                    } catch (Exception $e) {
                        return 1;
                    }
                })->describe($action->getCommandDescription());
            });
    }

    public function flushDiscoveryCache(): bool
    {
        try {
            if ($cache = app()->make(CacheManager::class)) {
                return $cache->forget(ActionDiscovery::$cacheKey);
            }
        } catch (BindingResolutionException $e) {
            return false;
        }
    }
}
