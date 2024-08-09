<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithJsonAndHtmlResponseTest
{
    use AsController;

    public function handle(ActionRequest $request)
    {
        return $request->left + $request->right;
    }

    public function jsonResponse($result, ActionRequest $request): JsonResponse
    {
        return response()->json([
            'result' => $result,
            'from' => 'json',
        ]);
    }

    public function htmlResponse($result, ActionRequest $request)
    {
        return response("Result: {$result}\nFrom: html");
    }
}

beforeEach(function () {
    // Given we register the action as a controller.
    Route::post('/controller', AsControllerWithJsonAndHtmlResponseTest::class);
});

it('can return a JSON specific response', function () {
    // When we send a JSON request to that controller.
    $response = $this->postJson('/controller', [
        'left' => 1,
        'right' => 2,
    ]);

    // Then we receive a JSON response.
    $response->assertOk();
    $response->assertExactJson([
        'result' => 3,
        'from' => 'json',
    ]);
});

it('can return an HTML specific response', function () {
    // When we send an HTML request to that controller.
    $response = $this->post('/controller', [
        'left' => 1,
        'right' => 2,
    ]);

    // Then we receive an HTML response.
    $response->assertOk();
    $response->assertSee('Result: 3');
    $response->assertSee('From: html');
});
