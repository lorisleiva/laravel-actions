<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsControllerWithImplicitBindingsTest
{
    use AsController;

    /**
     * This middleware is included by default in
     * both the `web` and `api` middleware groups.
     */
    public function getControllerMiddleware(): array
    {
        return [
            SubstituteBindings::class,
        ];
    }

    public function handle(User $user)
    {
        return $user;
    }
}

it('supports implicit route model binding', function () {
    // Given we have a route registering the controller.
    Route::get('/users/{user}', AsControllerWithImplicitBindingsTest::class);

    // And an existing user.
    loadMigrations();
    createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    // When we call that endpoint with that user's id.
    $response = $this->getJson('users/42');

    // Then we implicitly fetched the user from the provided id.
    $response->assertOk();
    $response->assertJson([
        'id' => 42,
        'name' => 'John Doe',
    ]);
});

it('supports custom implicit route model binding', function () {
    // Given we have a registered route binding the user using its name.
    Route::get('/users/{user:name}', AsControllerWithImplicitBindingsTest::class);

    // And an existing user.
    loadMigrations();
    createUser([
        'id' => 42,
        'name' => 'some-name',
    ]);

    // When we call that endpoint with that user's name.
    $response = $this->getJson('users/some-name');

    // Then we implicitly fetched the user from the provided id.
    $response->assertOk();
    $response->assertJson([
        'id' => 42,
        'name' => 'some-name',
    ]);
});
