<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerTest
{
    use AsController;

    public static int $constructed = 0;
    public static int $handled = 0;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle($left, $right, Filesystem $filesystem)
    {
        static::$handled++;

        return response()->json([
            'addition' => $left + $right,
        ]);
    }
}

beforeEach(function () {
    AsControllerTest::$constructed = 0;
    AsControllerTest::$handled = 0;
});

it('can be run as a controller', function () {
    $this->withoutExceptionHandling();

    // Given the action is registered as a controller.
    Route::get('/calculator/{left}/plus/{right}', AsControllerTest::class);

    // When we call that route.
    $reponse = $this->getJson('/calculator/5/plus/3');

    // Then we receive a successful response.
    $reponse->assertOk()->assertExactJson(['addition' => 8]);
});

it('does not construct the action when registering the route', function () {
    // When we register an action as a controller.
    Route::get('/some/endpoint', AsControllerTest::class);

    // Then it has not been constructed.
    expect(AsControllerTest::$constructed)->toBe(0);
});

it('constructs the action and runs the handle method exactly once per request', function () {
    // Given the action is registered as a controller.
    Route::get('/calculator/{left}/plus/{right}', AsControllerTest::class);

    // When we call that same route twice.
    $reponseA = $this->getJson('/calculator/1/plus/2');
    $reponseB = $this->getJson('/calculator/2/plus/3');

    // Then both response were successful
    $reponseA->assertOk()->assertExactJson(['addition' => 3]);
    $reponseB->assertOk()->assertExactJson(['addition' => 5]);

    // And the action was constructed only once.
    expect(AsControllerTest::$constructed)->toBe(2);

    // But handled exactly twice.
    expect(AsControllerTest::$handled)->toBe(2);
});

it('provides a magic invoke method to enable the action to be registered as a route', function () {
    // When an action uses the `AsController` trait.
    // Then it has the `__invoke` method.
    expect(method_exists(AsControllerTest::class, '__invoke'))->toBeTrue();
});
