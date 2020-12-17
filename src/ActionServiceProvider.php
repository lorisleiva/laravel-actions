<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\DesignPatterns\CommandDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ControllerDesignPattern;
use Lorisleiva\Actions\DesignPatterns\ListenerDesignPattern;

class ActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $manager = new ActionManager($this->app, [
            new ControllerDesignPattern(),
            new ListenerDesignPattern(),
            new CommandDesignPattern(),
        ]);

        $this->app->instance(ActionManager::class, $manager);
        $this->extendActions($manager);
    }

    public function boot()
    {
        //
    }

    protected function extendActions(ActionManager $manager)
    {
        $this->app->beforeResolving(function ($abstract, $parameters, Application $app) use ($manager) {
            if (! class_exists($abstract) || $app->resolved($abstract)) {
                return;
            }

            $manager->extend($abstract);
        });
    }
}
