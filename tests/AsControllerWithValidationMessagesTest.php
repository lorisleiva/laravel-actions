<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithValidationMessagesTest
{
    use AsController;

    public function rules()
    {
        return [
            'left' => ['required'],
            'right' => ['required'],
        ];
    }

    public function getValidationMessages()
    {
        return [
            'left.required' => 'You forgot the left operand.',
        ];
    }

    public function getValidationAttributes()
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

it('fails validation with custom messages and attributes', function () {
    // Given an action with custom messages registered as a controller.
    Route::post('/controller', AsControllerWithValidationMessagesTest::class);

    // When we provide invalid data.
    $reponse = $this->postJson('/controller');

    // Then we receive a validation error with these custom messages.
    $reponse->assertStatus(422);
    $reponse->assertJsonValidationErrors([
        'left' => 'You forgot the left operand',
        'right' => 'The right operand field is required.',
    ]);
});
