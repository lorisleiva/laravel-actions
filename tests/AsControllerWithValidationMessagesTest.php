<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithValidationMessagesTest
{
    use AsController;

    public function rules(): array
    {
        return [
            'left' => ['required'],
            'right' => ['required'],
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'left.required' => 'You forgot the left operand.',
        ];
    }

    public function getValidationAttributes(): array
    {
        return [
            'right' => 'right operand',
        ];
    }

    public function handle(ActionRequest $request): int
    {
        return $request->left + $request->right;
    }
}

beforeEach(function () {
    // Given an action with custom messages registered as a controller.
    Route::post('/controller', AsControllerWithValidationMessagesTest::class);
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


it('fails validation with custom messages and attributes', function () {
    // When we provide invalid data.
    $response = $this->postJson('/controller');

    // Then we receive a validation error with these custom messages.
    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'left' => 'You forgot the left operand',
        'right' => 'The right operand field is required.',
    ]);
});
