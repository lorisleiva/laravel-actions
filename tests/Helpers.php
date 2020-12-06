<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Tests\Stubs\User;

function loadMigrations()
{
    test()->loadLaravelMigrations();
}

function createUser(array $data = [])
{
    return User::create(array_merge([
        'name' => 'John Doe',
        'email' => rand() . '@gmail.com',
        'password' => bcrypt('secret'),
    ], $data));
}

function parseSerializedData(string $serialized): array
{
    $parsedObject = unserialize($serialized, ['allowed_classes' => false]);

    return collect((array) $parsedObject)
        ->mapWithKeys(function ($value, $key) {
            $key = Str::of($key)->afterLast("\x00");

            return [(string) $key => $value];
        })
        ->toArray();
}

function assertJobPushed(string $class, ?Closure $callback = null)
{
    Queue::assertPushed(JobDecorator::class, function (JobDecorator $job) use ($class, $callback) {
        if (! $job->getAction() instanceof $class) {
            return false;
        }

        return $callback ? $callback($job) : true;
    });
}

function assertJobPushedWith(string $class, array $parameters = [])
{
    assertJobPushed($class, function (JobDecorator $job) use ($class, $parameters) {
        return $job->getParameters() === $parameters;
    });
}
