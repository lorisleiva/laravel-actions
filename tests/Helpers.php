<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Console\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Tests\Stubs\User;

function loadMigrations(): void
{
    test()->loadLaravelMigrations();
}

function createUser(array $data = []): User
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

function registerCommands(array $commands): void
{
    $artisan = new Application(app(), app('events'), app()->version());

    Artisan::setArtisan($artisan->resolveCommands($commands));
}

function assertJobPushed(string $class, ?Closure $callback = null): void
{
    Queue::assertPushed(ActionManager::$jobDecorator, function (JobDecorator $job) use ($class, $callback) {
        if (! $job->decorates($class)) {
            return false;
        }

        return $callback ? $callback($job) : true;
    });
}
