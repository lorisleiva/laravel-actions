<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\ActionServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ActionServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
    }
}
