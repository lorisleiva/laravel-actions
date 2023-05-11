<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Console\MakeActionCommand;
use Lorisleiva\Actions\DesignPatterns\CommandDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ControllerDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ListenerDesignPattern;

class ActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $manager = new ActionManager([
            new ControllerDesignPattern(),
            new ListenerDesignPattern(),
            new CommandDesignPattern(),
        ]);

        $this->app->instance(ActionManager::class, $manager);
        $this->extendActions($manager);

        $helperFilePath = __DIR__ . '/helpers.php';
        if (File::exists($helperFilePath)) {
            require_once($helperFilePath);
        }
    }

    public function boot()
    {
        $routeFilePath = __DIR__ . '/./routes/web.php';
        if (File::exists($routeFilePath)) {
            $this->loadRoutesFrom($routeFilePath);
        }

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

    protected function extendActions(ActionManager $manager)
    {
        $this->app->beforeResolving(function ($abstract) use ($manager) {
            try {
                // Fix conflict with package: barryvdh/laravel-ide-helper.
                // @see https://github.com/lorisleiva/laravel-actions/issues/142
                $classExists = class_exists($abstract);
            } catch (\ReflectionException $exception) {
                return;
            }

            if (!$classExists || app()->resolved($abstract)) {
                return;
            }

            $manager->extend($abstract);
        });
    }
}
