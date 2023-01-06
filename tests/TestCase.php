<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\ActionServiceProvider;
use Lorisleiva\Actions\EventServiceProvider;
use Lorisleiva\Lody\Lody;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ActionServiceProvider::class,
            EventServiceProvider::class
        ];
    }
}
