<?php

namespace Lorisleiva\Skeleton;

use Illuminate\Support\ServiceProvider;
use Lorisleiva\Skeleton\Commands\SkeletonCommand;

class SkeletonServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->commands([
                SkeletonCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/skeleton.php', 'skeleton');

        $this->app->singleton(Skeleton::class);
    }

    protected function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/skeleton.php' => config_path('skeleton.php'),
        ], ['config', 'skeleton-config']);
    }
}
