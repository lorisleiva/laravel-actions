<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithCustomValidatorTest
{
    use AsController;

    public function getValidator(Factory $factory, ActionRequest $request): Validator
    {
        $rules = [
            'left' => ['required'],
            'right' => ['required'],
        ];

        return $factory->make(
            $request->all(),
            $rules,
            ['left.required' => 'You forgot the left operand.'],
            ['right' => 'right operand']
        );
    }

    public function handle(ActionRequest $request)
    {
        return $request->left + $request->right;
    }
}

beforeEach(function () {
    // Given an action with a custom validator registered as a controller.
    Route::post('/controller', AsControllerWithCustomValidatorTest::class);
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


it('fails validation', function () {
    // When we provide invalid data.
    $reponse = $this->postJson('/controller');

    // Then we receive a validation error with these custom messages.
    $reponse->assertStatus(422);
    $reponse->assertJsonValidationErrors([
        'left' => 'You forgot the left operand',
        'right' => 'The right operand field is required.',
    ]);
});
