<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsObjectWithModelBindingNullableTest
{
    use AsObject;

    protected static function shouldResolveModelBindings(): bool
    {
        return true;
    }

    public function handle(?User $user): ?User
    {
        return $user;
    }
}

it('handles nullable model parameters with null value', function () {
    $result = AsObjectWithModelBindingNullableTest::run(null);

    expect($result)->toBeNull();
});

it('handles nullable model parameters with scalar value', function () {
    loadMigrations();
    createUser(['id' => 1, 'name' => 'John']);

    $result = AsObjectWithModelBindingNullableTest::run(1);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe(1);
});

