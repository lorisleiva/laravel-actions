<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Console\MakeActionCommand;
use Lorisleiva\Actions\DesignPatterns\CommandDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ControllerDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ListenerDesignPattern;
use Lorisleiva\Actions\DesignPatterns\PipelineDesignPattern;

class ActionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(ActionManager::class, function () {
            return new ActionManager([
                new ControllerDesignPattern(),
                new ListenerDesignPattern(),
                new CommandDesignPattern(),
                new PipelineDesignPattern(),
            ]);
        });

        $this->extendActions();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish Stubs File
            $this->publishes([
                __DIR__ . '/Console/stubs/action.stub' => base_path('stubs/action.stub'),
            ], 'stubs');

            // Register the make:action generator command.
            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }

    protected function extendActions(): void
    {
        $this->app->beforeResolving(function ($abstract, $parameters, Application $app) {
            if ($abstract === ActionManager::class) {
                return;
            }

            try {
                // Fix conflict with package: barryvdh/laravel-ide-helper.
                // @see https://github.com/lorisleiva/laravel-actions/issues/142
                $classExists = class_exists($abstract);
            } catch (\ReflectionException) {
                return;
            }

            if (! $classExists || $app->resolved($abstract)) {
                return;
            }

            $app->make(ActionManager::class)->extend($app, $abstract);
        });
    }
}
