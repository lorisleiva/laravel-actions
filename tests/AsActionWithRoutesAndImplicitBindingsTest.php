<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Facades\Actions;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsActionWithRoutesAndImplicitBindingsTest
{
    use AsAction;

    public static function routes(Router $router): void
    {
        $router->get('/from-action/users/{user}', static::class);
        $router->get('/from-action/users-by-name/{user:name}', static::class);
    }

    public function handle(User $user): User
    {
        return $user;
    }
}

it('supports implicit route model binding when using AsAction and route is defined in routes() method', function () {
    Actions::registerRoutesForAction(AsActionWithRoutesAndImplicitBindingsTest::class);

    loadMigrations();
    createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    $response = $this->getJson('/from-action/users/42');

    $response->assertOk();
    $response->assertJson([
        'id' => 42,
        'name' => 'John Doe',
    ]);
});

it('supports custom route model binding keys when using AsAction and route is defined in routes() method', function () {
    Actions::registerRoutesForAction(AsActionWithRoutesAndImplicitBindingsTest::class);

    loadMigrations();
    createUser([
        'id' => 42,
        'name' => 'some-name',
    ]);

    $response = $this->getJson('/from-action/users-by-name/some-name');

    $response->assertOk();
    $response->assertJson([
        'id' => 42,
        'name' => 'some-name',
    ]);
});
