<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Facades\Actions;

class AsControllerWithRoutesTest
{
    use AsController;

    public static function routes(Router $router): void
    {
        $router->get('/controller/with/routes', static::class);
    }

    public function handle(): JsonResponse
    {
        return response()->json(['Ok'], 200);
    }
}

it('can register its routes directly in the action', function () {
    // Given we register the routes on a ServiceProvider.
    Actions::registerRoutesForAction(AsControllerWithRoutesTest::class);

    // When we call the route defined on the action.
    $response = $this->getJson('/controller/with/routes');

    // Then we receive the expected response.
    $response->assertOk()->assertExactJson(['Ok']);
});
