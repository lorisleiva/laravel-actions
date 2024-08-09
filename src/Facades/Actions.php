<?php

namespace Lorisleiva\Actions\Facades;

use Illuminate\Support\Facades\Facade;
use Lorisleiva\Actions\ActionManager;

/**
 * @see ActionManager
 * @method static void registerRoutes($paths = 'app/Actions')
 * @method static void registerCommands($paths = 'app/Actions')
 */
class Actions extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ActionManager::class;
    }
}
