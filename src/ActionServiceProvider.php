<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Dispatcher as IlluminateBusDispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\MakeActionCommand;
use Lorisleiva\Actions\Repositories\ActionRepository;
use Lorisleiva\Actions\Repositories\AutoloaderActionRepository;
use Lorisleiva\Actions\Repositories\TestActionRepository;

class ActionServiceProvider extends ServiceProvider
{

    public function boot()
    {
        Facade::clearResolvedInstance('events');
        $this->app->extend('events', function ($dispatcher, $app) {
            return new EventDispatcherDecorator($dispatcher, $app);
        });

        $this->app->extend(IlluminateBusDispatcher::class, function ($dispatcher, $app) {
            return new BusDispatcher($app, function ($connection = null) use ($app) {
                return $app->make(QueueFactoryContract::class)->connection($connection);
            });
        });

        Route::macro('actions', function ($group) {
            $this->namespace('\App\Actions')->group($group);
        });

        if ($this->app->runningUnitTests()) {
            $this->app->bind(ActionRepository::class, TestActionRepository::class);
        } else {
            $this->app->bind(ActionRepository::class, AutoloaderActionRepository::class);
        }

        $manager = new ActionManager($this->app->make(ActionRepository::class));
        $this->app->instance('action-manager', $manager);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
            ]);
            $manager->registerActionCommands();
        }
    }
}
