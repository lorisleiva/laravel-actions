<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Console\MakeActionCommand;
use Lorisleiva\Actions\DesignPatterns\CommandDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ControllerDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ListenerDesignPattern;

class ActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->scoped(ActionManager::class, function () {
            return new ActionManager([
                new ControllerDesignPattern(),
                new ListenerDesignPattern(),
                new CommandDesignPattern(),
            ]);
        });

        $this->extendActions();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // publish Stubs File
            $this->publishes([
                __DIR__ . '/Console/stubs/action.stub' => base_path('stubs/action.stub'),
            ], 'stubs');

            // Register the make:action generator command.
            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }

    protected function extendActions()
    {
        $this->app->beforeResolving(function ($abstract, $parameters, Application $app) {
            if ($abstract === ActionManager::class) {
                return;
            }

            try {
                // Fix conflict with package: barryvdh/laravel-ide-helper.
                // @see https://github.com/lorisleiva/laravel-actions/issues/142
                $classExists = class_exists($abstract);
            } catch (\ReflectionException $exception) {
                return;
            }

            if (! $classExists || $app->resolved($abstract)) {
                return;
            }

            $app->make(ActionManager::class)->extend($app, $abstract);
        });
    }
}
