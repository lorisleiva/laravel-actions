<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithCustomFailuresTest
{
    use AsController;

    public function authorize(ActionRequest $request)
    {
        return $request->get('operation') !== 'unauthorized';
    }

    public function rules()
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

    public function getAuthorizationFailure()
    {
        abort(400, 'Custom authorization failure.');
    }

    public function getValidationFailure()
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
    $reponse = $this->postJson('/controller', [
        'left' => 1,
        'right' => 2,
    ]);

    // Then we receive a successful response.
    $reponse->assertOk();
    $reponse->assertExactJson([3]);
});

it('fails authorization with a custom failure', function () {
    // When we make an unauthorized request.
    $reponse = $this->postJson('/controller', [
        'operation' => 'unauthorized',
    ]);

    // Then we receive a custom authorization error.
    $reponse->assertStatus(400);
    $reponse->assertExactJson([
        'message' => 'Custom authorization failure.',
    ]);
});

it('fails validation with a custom failure', function () {
    // When we provide invalid data.
    $reponse = $this->postJson('/controller');

    // Then we receive a custom validation error.
    $reponse->assertStatus(400);
    $reponse->assertExactJson([
        'message' => 'Custom validation failure.',
    ]);
});
