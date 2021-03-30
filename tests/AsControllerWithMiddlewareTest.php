<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithMiddlewareTest
{
    use AsController;

    public function getControllerMiddleware()
    {
        return [
            function (Request $request, $next) {
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
