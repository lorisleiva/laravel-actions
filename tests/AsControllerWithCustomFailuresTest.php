<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithCustomFailuresTest
{
    use AsController;

    public function authorize(ActionRequest $request): bool
    {
        return $request->get('operation') !== 'unauthorized';
    }

    public function rules(): array
    {
        return [
            'left' => ['required'],
            'right' => ['required'],
        ];
    }

    public function handle(ActionRequest $request)
    {
        return $request->left + $request->right;
    }

    public function getAuthorizationFailure(): void
    {
        abort(400, 'Custom authorization failure.');
    }

    public function getValidationFailure(): void
    {
        abort(400, 'Custom validation failure.');
    }
}

beforeEach(function () {
    // Given an action with custom failure callbacks registered as a controller.
    Route::post('/controller', AsControllerWithCustomFailuresTest::class);
});

it('passes validation', function () {
    // When we provide valid data.
    $response = $this->postJson('/controller', [
        'left' => 1,
        'right' => 2,
    ]);

    // Then we receive a successful response.
    $response->assertOk();
    $response->assertExactJson([3]);
});

it('fails authorization with a custom failure', function () {
    // When we make an unauthorized request.
    $response = $this->postJson('/controller', [
        'operation' => 'unauthorized',
    ]);

    // Then we receive a custom authorization error.
    $response->assertStatus(400);
    $response->assertExactJson([
        'message' => 'Custom authorization failure.',
    ]);
});

it('fails validation with a custom failure', function () {
    // When we provide invalid data.
    $response = $this->postJson('/controller');

    // Then we receive a custom validation error.
    $response->assertStatus(400);
    $response->assertExactJson([
        'message' => 'Custom validation failure.',
    ]);
});
