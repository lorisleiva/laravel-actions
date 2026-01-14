<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsObjectWithoutModelBindingTest
{
    use AsObject;

    public function handle(User $user): User
    {
        return $user;
    }
}

it('requires explicit model instances when model binding is disabled', function () {
    loadMigrations();
    $user = createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    $result = AsObjectWithoutModelBindingTest::run($user);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe(42);
    expect($result->name)->toBe('John Doe');
});

it('works when passing model instance directly even without opt-in', function () {
    loadMigrations();
    $user = createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    $result = AsObjectWithoutModelBindingTest::run($user);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe(42);
});
