<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsObjectWithModelBindingTest
{
    use AsObject;

    protected static function shouldResolveModelBindings(): bool
    {
        return true;
    }

    public function handle(User $user): User
    {
        return $user;
    }
}

it('resolves model binding when passing scalar value to run()', function () {
    loadMigrations();
    $user = createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    $result = AsObjectWithModelBindingTest::run(42);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe(42);
    expect($result->name)->toBe('John Doe');
});

it('works when passing model instance directly', function () {
    loadMigrations();
    $user = createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    $result = AsObjectWithModelBindingTest::run($user);

    expect($result)->toBe($user);
    expect($result->id)->toBe(42);
});

it('throws exception when model not found', function () {
    loadMigrations();

    expect(fn() => AsObjectWithModelBindingTest::run(999))
        ->toThrow(ModelNotFoundException::class);
});

