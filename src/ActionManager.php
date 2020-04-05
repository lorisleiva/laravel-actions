<?php

namespace Lorisleiva\Actions;

use Artisan;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Collection;

class ActionManager
{
    /**
     * @var ActionDiscovery
     */
    private $discovery;

    /**
     * ActionManager constructor.
     */
    public function __construct()
    {
        $this->discovery = new ActionDiscovery();
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

    /**
     * Get instances of all discovered Action classes
     * @return Collection
     */
    public function getActions(): Collection
    {
        return $this->discovery->getActions();
    }

    /**
     * Remove currently discovered actions from cache
     * @return bool
     */
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
