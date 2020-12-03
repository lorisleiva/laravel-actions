<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\MakeActionCommand;

class ActionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(ActionManager::class);
    }
}
