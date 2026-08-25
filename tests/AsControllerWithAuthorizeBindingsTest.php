<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\WithAttributes;
use Lorisleiva\Actions\Tests\Stubs\User;

class AsControllerWithAuthorizeBindingsTest
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

    /**
     * Note that the parameter is deliberately not named after the route
     * parameter to ensure the bound model is matched by type — exactly
     * like it is on the action's controller method.
     */
    public function authorize(User $theUser): bool
    {
        return $theUser->name !== 'unauthorized';
    }

    public function handle(User $user): User
    {
        return $user;
    }
}

class AsControllerWithAuthorizeScalarBindingsTest
{
    use AsController;

    public function authorize(string $someRouteParameter): bool
    {
        return $someRouteParameter !== 'unauthorized';
    }

    public function handle(string $someRouteParameter): array
    {
        return [$someRouteParameter];
    }
}

class AsControllerWithAuthorizeRequestOnBoundRouteTest
{
    use AsController;

    public function authorize(ActionRequest $request): bool
    {
        return $request->route('someRouteParameter') !== 'unauthorized';
    }

    public function handle(ActionRequest $request, string $someRouteParameter): array
    {
        return [$someRouteParameter];
    }
}

class AsControllerWithAuthorizeOptionalBindingTest
{
    use AsController;

    public function authorize(string $someRouteParameter = 'default'): bool
    {
        return $someRouteParameter !== 'unauthorized';
    }

    public function handle(string $someRouteParameter = 'default'): array
    {
        return [$someRouteParameter];
    }
}

class AsControllerWithAuthorizeBindingsAndAttributesTest
{
    use AsController;
    use WithAttributes;

    public static string $authorizedWith = '';

    public function authorize(string $someRouteParameter = 'not injected'): bool
    {
        static::$authorizedWith = $someRouteParameter;

        return true;
    }

    public function handle(ActionRequest $request, string $someRouteParameter): array
    {
        $this->fillFromRequest($request)->validateAttributes();

        return [static::$authorizedWith];
    }
}

it('injects bound route models into the authorize method', function () {
    // Given we have a route registering the controller.
    Route::get('/users/{user}', AsControllerWithAuthorizeBindingsTest::class);

    // And an existing user.
    loadMigrations();
    createUser([
        'id' => 42,
        'name' => 'John Doe',
    ]);

    // When we call that endpoint with that user's id.
    $response = $this->getJson('users/42');

    // Then the authorize method received the resolved user and not an empty model.
    $response->assertOk();
    $response->assertJson([
        'id' => 42,
        'name' => 'John Doe',
    ]);
});

it('fails authorization based on the bound route model', function () {
    // Given we have a route registering the controller.
    Route::get('/users/{user}', AsControllerWithAuthorizeBindingsTest::class);

    // And an existing user the action refuses to authorize.
    loadMigrations();
    createUser([
        'id' => 42,
        'name' => 'unauthorized',
    ]);

    // When we call that endpoint with that user's id.
    $response = $this->getJson('users/42');

    // Then we receive a forbidden error.
    $response->assertForbidden();
});

it('injects scalar route parameters into the authorize method', function () {
    // Given we have a route registering the controller.
    Route::get('/authorize-bindings/{someRouteParameter}', AsControllerWithAuthorizeScalarBindingsTest::class);

    // Then the route parameter is provided to the authorize method.
    $this->getJson('/authorize-bindings/authorized')->assertOk()->assertExactJson(['authorized']);
    $this->getJson('/authorize-bindings/unauthorized')->assertForbidden();
});

it('still injects the action request on a route with parameters', function () {
    // Given we have a route registering the controller.
    Route::get('/authorize-request/{someRouteParameter}', AsControllerWithAuthorizeRequestOnBoundRouteTest::class);

    // Then the surplus route parameters do not break the existing signature.
    $this->getJson('/authorize-request/authorized')->assertOk()->assertExactJson(['authorized']);
    $this->getJson('/authorize-request/unauthorized')->assertForbidden();
});

it('uses the default value of a missing optional route parameter', function () {
    // Given we have a route registering the controller with an optional parameter.
    Route::get('/authorize-optional/{someRouteParameter?}', AsControllerWithAuthorizeOptionalBindingTest::class);

    // Then the default value is used when the parameter is missing.
    $this->getJson('/authorize-optional')->assertOk()->assertExactJson(['default']);
    $this->getJson('/authorize-optional/provided')->assertOk()->assertExactJson(['provided']);
});

it('does not inject route parameters when validating attributes', function () {
    // Given we have a route registering a controller using attributes.
    Route::get('/authorize-attributes/{someRouteParameter}', AsControllerWithAuthorizeBindingsAndAttributesTest::class);

    // When we call that endpoint.
    $response = $this->getJson('/authorize-attributes/some-value');

    // Then the authorize method was called without the route parameters,
    // since attribute validation is triggered manually by the action.
    $response->assertOk();
    $response->assertExactJson(['not injected']);
});
