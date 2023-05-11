<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Filesystem\Filesystem;
use Lorisleiva\Actions\Concerns\AsController;

class AsActionControllerTest
{
    use AsController;

    public static int $constructed = 0;
    public static int $handled = 0;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle(\Illuminate\Http\Request $request, Filesystem $filesystem)
    {
        static::$handled++;

        return response()->json([
            'addition' => $request->input('left') + $request->input('right'),
        ]);
    }
}

beforeEach(function () {
    AsActionControllerTest::$constructed = 0;
    AsActionControllerTest::$handled = 0;
});

it('can be run as an action controller', function () {
    // Given the action is registered as an action controller.

    // When we call that route.
    $response = $this->get(action_route(AsActionControllerTest::class, ['left' => 3, 'right' => 5]));

    // Then we receive a successful response.
    $response->assertOk()->assertExactJson(['addition' => 8]);
});

it('does not construct the action when registering the route', function () {
    // It has not been constructed.
    expect(AsActionControllerTest::$constructed)->toBe(0);
});

it('constructs the action and runs the handle method exactly once per request', function () {
    // Given the action is registered as an action controller.

    // When we call that same route twice.
    $responseA = $this->get(action_route(AsActionControllerTest::class, ['left' => 1, 'right' => 2]));
    $responseB = $this->get(action_route(AsActionControllerTest::class, ['left' => 2, 'right' => 3]));

    // Then both response were successful
    $responseA->assertOk()->assertExactJson(['addition' => 3]);
    $responseB->assertOk()->assertExactJson(['addition' => 5]);

    // And the action was constructed only once.
    expect(AsActionControllerTest::$constructed)->toBe(2);

    // But handled exactly twice.
    expect(AsActionControllerTest::$handled)->toBe(2);
});
