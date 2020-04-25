<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Dispatcher as IlluminateBusDispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\MakeActionCommand;
use Lorisleiva\Actions\Facades\Actions;

class ActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind the ActionManager accessible via the Actions Facade.
        $this->app->singleton(ActionManager::class);
    }

    public function boot()
    {
        // Load all actions and register their commands and/or routes.
        Actions::load();

        // Extend the EventDispatcher in order to run as listeners.
        Facade::clearResolvedInstance('events');
        $this->app->extend('events', function ($dispatcher, $app) {
            return new EventDispatcherDecorator($dispatcher, $app);
        });

        // Extend the BusDispatcher in order to run as jobs.
        $this->app->extend(IlluminateBusDispatcher::class, function ($dispatcher, $app) {
            return new BusDispatcher($app, function ($connection = null) use ($app) {
                return $app->make(QueueFactoryContract::class)->connection($connection);
            });
        });

        // Add a helper macro to register actions in routes.
        Route::macro('actions', function ($group) {
            $this->namespace('\App\Actions')->group($group);
        });

        // Register the make:action generator command.
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }
}
