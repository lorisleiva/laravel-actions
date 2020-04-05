<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Dispatcher as IlluminateBusDispatcher;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\FlushDiscoveryCache;
use Lorisleiva\Actions\Commands\MakeActionCommand;

class ActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-actions.php', 'laravel-actions'
        );
    }


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

        $config = config()->get('laravel-actions');
        if ($this->app->runningUnitTests()) {
            $config = [
                'discovery' => [
                    'autoloader' => false,
                    'folders' => [
                        __DIR__ . '/../tests/Actions'
                    ],
                    'caching' => [
                        'enabled' => false
                    ]
                ]
            ];
        }
        $manager = new ActionManager($config);
        $this->app->instance(ActionManager::class, $manager);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-actions.php' => config_path('laravel-actions.php'),
            ]);
            $this->commands([
                MakeActionCommand::class,
                FlushDiscoveryCache::class
            ]);
            if (Arr::get($config, 'discovery.autoloader') && Arr::get($config, 'discovery.caching.enabled')) {
                // Flush the cache when package:discover has run (happens when Composer autoload is dumped)
                $this->app->make('events')
                    ->listen(CommandFinished::class, static function (CommandFinished $event) use ($manager) {
                        if ($event->command === 'package:discover') {
                            $manager->flushDiscoveryCache();
                        }
                    });
            }
            $manager->registerActionCommands();
        }
    }
}
