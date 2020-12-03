<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\MakeActionCommand;

class ActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $designPatternManager = new DesignPatternManager([
            new ControllerDesignPattern(),
            new ListenerDesignPattern(),
            new CommandDesignPattern(),
        ]);

        $this->app->instance(DesignPatternManager::class, $designPatternManager);
        $this->extendActions($designPatternManager);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
            ]);
        }
    }

    protected function extendActions(DesignPatternManager $manager)
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
