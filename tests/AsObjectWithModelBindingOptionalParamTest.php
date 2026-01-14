<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsObjectWithModelBindingOptionalParamTest
{
    use AsObject;

    protected static function shouldResolveModelBindings(): bool
    {
        return true;
    }

    public function handle(User $user, string $status = 'active'): array
    {
        return [
            'user' => $user,
            'status' => $status,
        ];
    }
}

it('handles optional parameters correctly', function () {
    loadMigrations();
    createUser(['id' => 1, 'name' => 'John']);

    $result = AsObjectWithModelBindingOptionalParamTest::run(1);

    expect($result['user'])->toBeInstanceOf(User::class);
    expect($result['user']->id)->toBe(1);
    expect($result['status'])->toBe('active');
});

it('handles optional parameters with provided value', function () {
    loadMigrations();
    createUser(['id' => 1, 'name' => 'John']);

    $result = AsObjectWithModelBindingOptionalParamTest::run(1, 'inactive');

    expect($result['user'])->toBeInstanceOf(User::class);
    expect($result['user']->id)->toBe(1);
    expect($result['status'])->toBe('inactive');
});

