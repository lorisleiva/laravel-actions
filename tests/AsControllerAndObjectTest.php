<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class AsControllerAndObjectTest
{
    use AsObject;
    use AsController;

    public function handle(int $left, int $right): int
    {
        return $left + $right;
    }

    public function asController($left, ActionRequest $request)
    {
        $addition = $this->handle($left, $request->get('right'));

        return response()->json(compact('addition'));
    }
}

it('works as an object', function () {
    // When we run the action as an object.
    $result = AsControllerAndObjectTest::run(1, 2);

    // Then we get the expected result.
    expect($result)->toBe(3);
});

it('works as a controller', function () {
    // Given the action is registered as a controller.
    Route::post('/add/{left}', AsControllerAndObjectTest::class);

    // When we call that route.
    $response = $this->postJson('/add/1', ['right' => 2]);

    // Then we receive a successful response.
    $response->assertOk()->assertExactJson(['addition' => 3]);
});
