<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Stubs\User;

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

    public function createUser($data = [])
    {
        return User::create(array_merge([
            'name' => 'John Doe', 
            'email' => 'john.doe@gmail.com',
            'password' => bcrypt('secret'),
        ], $data));
    }
}