<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithPolicyResponsesTest
{
    use AsController;

    public function authorize(ActionRequest $request): Response
    {
        if ($request->get('operation') === 'unauthorized') {
            return Response::deny('My custom authorization message.');
        }

        return Response::allow();
    }

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    // Given the action returning policy responses is registered as a controller.
    Route::post('/controller', AsControllerWithPolicyResponsesTest::class);
});

it('passes authorization', function () {
    // When we make an authorised request.
    $response = $this->postJson('/controller');

    // Then we receive a successful response.
    $response->assertOk();
});

it('fails authorization', function () {
    // When we make an unauthorised request.
    $response = $this->postJson('/controller', [
        'operation' => 'unauthorized',
    ]);

    // Then we receive a forbidden error with our custom message.
    $response->assertForbidden();
    $response->assertExactJson([
        'message' => 'My custom authorization message.',
    ]);
});
