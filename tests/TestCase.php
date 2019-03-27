<?php

namespace Lorisleiva\Actions\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Lorisleiva\Actions\ActionServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        //
    }
}