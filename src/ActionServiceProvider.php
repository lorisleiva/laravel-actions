<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Dispatcher as IlluminateBusDispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Commands\MakeActionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeActionCommand::class,
            ]);
            $this->registerActionsAsCommands();
        }
    }

    private function registerActionsAsCommands()
    {
        $reflection_classes = $this->discoverActionClasses();
        foreach ($reflection_classes as $reflection_class) {
            $instance = app()->make($reflection_class->getName());
            $signature_property = $reflection_class->getProperty('signature');
            $signature_property->setAccessible(true);
            $command_signature = $signature_property->getValue($instance);
            if ($command_signature === '') {
                continue;
            }
            $description_property = $reflection_class->getProperty('description');
            $description_property->setAccessible(true);
            $command_description = $description_property->getValue($instance);
            \Artisan::command($command_signature, function () use ($reflection_class) {
                $action_class = $reflection_class->getName();
                /** @var InputInterface $input */
                $input = $this->input;
                $attributes = array_merge($input->getArguments(), $input->getOptions());
                /** @var Action $action */
                $action = new $action_class($attributes);
                $action->runAsCommand();
                return 0;
            })->describe($command_description);
        }
    }

    /**
     * Returns ReflectionClasses for each Action class in the project
     * @return \Generator|\ReflectionClass[]
     */
    private function discoverActionClasses()
    {
        $app_namespace = app()->getNamespace();
        $app_folder = app_path();
        $files = Finder::create()
            ->in($app_folder)
            ->name('*.php')
            ->files();
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $class_fqn = $app_namespace . implode('\\', explode(DIRECTORY_SEPARATOR, mb_substr($file->getRelativePathname(), 0, -4)));
            try {
                $reflection = new \ReflectionClass($class_fqn);
                if ($reflection->isSubclassOf("Lorisleiva\Actions\Action")) {
                    yield $reflection;
                }
            } catch (\ReflectionException $e) {
            }
        }
    }
}
