<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Facades\Actions;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsControllerWithRoutesAndImplicitBindingsTest
{
    use AsController;

    public static function routes(Router $router): void
    {
        $router->get('/from-action/users/{user}', static::class);
    }

    public function handle(User $user): User
    {
        return $user;
    }
}

it('supports implicit route model binding when route is defined in routes() method', function () {
    Actions::registerRoutesForAction(AsControllerWithRoutesAndImplicitBindingsTest::class);

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

