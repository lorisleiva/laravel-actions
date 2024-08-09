<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithValidatorCallbacksTest
{
    use AsController;

    public function withValidator(Validator $validator, ActionRequest $request): void
    {
        $validator->after(function (Validator $validator) use ($request) {
            if ($request->left >= 42) {
                $validator->errors()->add('left', 'Left must be smaller than 42.');
            }
        });
    }

    public function afterValidator(ActionRequest $request, $validator): void
    {
        if ($request->right % 2 === 0) {
            $validator->errors()->add('right', 'Right must be odd.');
        }
    }

    public function handle(ActionRequest $request): int
    {
        return $request->left + $request->right;
    }
}

beforeEach(function () {
    // Given the action is registered as a controller.
    Route::post('/controller', AsControllerWithValidatorCallbacksTest::class);
});

it('passes validation', function () {
    // When we provide valid data.
    $response = $this->postJson('/controller', [
        'left' => 30,
        'right' => 63,
    ]);

    // Then we receive a successful response.
    $response->assertOk()->assertExactJson([93]);
});

it('fails validation', function () {
    // When we provide invalid data.
    $response = $this->postJson('/controller', [
        'left' => 63,
        'right' => 30,
    ]);

    // Then we receive a validation error.
    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'left' => 'Left must be smaller than 42.',
        'right' => 'Right must be odd.',
    ]);
});
