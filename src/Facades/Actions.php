<?php

namespace Lorisleiva\Actions\Facades;

use Illuminate\Support\Facades\Facade;
use Lorisleiva\Actions\ActionManager;

/**
 * @see ActionManager
 */
class Actions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActionManager::class;
    }
}
