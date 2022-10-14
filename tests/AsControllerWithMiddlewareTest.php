<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithMiddlewareTest
{
    use AsController;

    public static int $middlewareCounter = 0;

    public function getControllerMiddleware()
    {
        return [
            function (Request $request, $next) {
                static::$middlewareCounter++;

                if ($request->get('operation') === 'middleware') {
                    return response()->json(['caught by middleware']);
                }

                return $next($request);
            },
        ];
    }

    public function handle(ActionRequest $request)
    {
        return $request->left + $request->right;
    }
}

it('can register controller middleware', function () {
    // Given the action is registered as a controller.
    Route::post('/calculator', AsControllerWithMiddlewareTest::class);

    // When we call that route.
    $response = $this->postJson('/calculator', [
        'operation' => 'middleware',
    ]);

    // Then we receive a successful response.
    $response->assertOk()->assertExactJson(['caught by middleware']);
});


it('works with route middleware too', function () {
    // Given the action is registered as a controller with a route middleware.
    Route::post('/calculator', AsControllerWithMiddlewareTest::class)
        ->middleware(RouteMiddleware::class);

    // When we call that route.
    $response = $this->postJson('/calculator', [
        'operation' => 'route',
    ]);

    // Then we were intercepted by the route middleware.
    $response->assertOk()->assertExactJson(['caught by route middleware']);
});

it('calls route and controller middleware exactly once', function () {
    // Given the action is registered as a controller with a route middleware.
    Route::post('/calculator', AsControllerWithMiddlewareTest::class)
        ->middleware(RouteMiddleware::class);

    // When we call that route.
    $response = $this->postJson('/calculator');

    // The we were intercepted by both the route and the controller middleware exactly one.
    expect(RouteMiddleware::$counter)->toBe(1);
    expect(AsControllerWithMiddlewareTest::$middlewareCounter)->toBe(1);
});

class RouteMiddleware {
    public static int $counter = 0;

    public function handle(Request $request, $next)
    {
        static::$counter++;

        if ($request->get('operation') === 'route') {
            return response()->json(['caught by route middleware']);
        }

        return $next($request);
    }
}
