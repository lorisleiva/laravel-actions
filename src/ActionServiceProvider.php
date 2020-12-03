<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\MakeActionCommand;
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
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }

    protected function extendActions(ActionManager $manager)
    {
        $this->app->beforeResolving(function ($abstract, $parameters, Application $app) use ($manager) {
            if (! class_exists($abstract) || $app->resolved($abstract)) {
                return;
            }

            if (empty($manager->getDesignPatternsFor($abstract))) {
                return;
            }

            $this->app->extend($abstract, function ($instance) use ($manager) {
                return $manager->identifyAndDecorate($instance);
            });
        });
    }
}
