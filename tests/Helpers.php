<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Console\Application;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Tests\Stubs\User;
use ReflectionClass;

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
    $decorator = match (true) {
        is_subclass_of($class, ShouldBeUnique::class) => ActionManager::$uniqueJobDecorator,
        class_exists(DebounceFor::class) && (new ReflectionClass($class))->getAttributes(DebounceFor::class) !== [] => ActionManager::$debounceJobDecorator,
        default => ActionManager::$jobDecorator,
    };

    Queue::assertPushed($decorator, function (JobDecorator $job) use ($class, $callback) {
        if (! $job->decorates($class)) {
            return false;
        }

        return $callback ? $callback($job) : true;
    });
}
