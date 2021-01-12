<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithAuthorizeAndRulesTest
{
    use AsController;

    public function authorize(ActionRequest $request): bool
    {
        return $request->get('operation') !== 'unauthorized';
    }

    public function rules(): array
    {
        return [
            'operation' => ['in:addition,substraction'],
            'left' => ['required', 'integer'],
            'right' => ['required', 'integer'],
        ];
    }

    public function handle(ActionRequest $request)
    {
        return $request->get('operation', 'addition') === 'addition'
            ? $request->left + $request->right
            : $request->left - $request->right;
    }
}

beforeEach(function () {
    // Given the action is registered as a controller.
    Route::post('/calculator', AsControllerWithAuthorizeAndRulesTest::class);
});

it('passes authorization and validation', function () {
    // When we call that route with the right request.
    $reponse = $this->postJson('/calculator', [
        'operation' => 'substraction',
        'left' => 5,
        'right' => 3,
    ]);

    // Then we receive a successful response.
    $reponse->assertOk()->assertExactJson([2]);
});

it('fails authorization', function () {
    // When we call that route with an unauthorized request.
    $reponse = $this->postJson('/calculator', [
        'operation' => 'unauthorized',
    ]);

    // Then we receive a forbidden error.
    $reponse->assertForbidden();
    $reponse->assertExactJson([
        'message' => 'This action is unauthorized.',
    ]);
});

it('fails validation', function () {
    // When we call that route with an invalid request.
    $reponse = $this->postJson('/calculator', [
        'operation' => 'multiplication',
        'left' => 'five',
    ]);

    // Then we receive a validation error.
    $reponse->assertStatus(422);
    $reponse->assertJsonValidationErrors([
        'operation' => 'The selected operation is invalid.',
        'left' => 'The left must be an integer.',
        'right' => 'The right field is required.',
    ]);
});

it('uses a new validator at every request', function () {
    $this->post('/calculator', ['operation' => 'addition', 'left' => 5])
        ->assertSessionHasErrors('right')
        ->assertSessionDoesntHaveErrors(['operation', 'left']);

    $this->post('/calculator', ['operation' => 'addition', 'right' => 5])
        ->assertSessionHasErrors('left')
        ->assertSessionDoesntHaveErrors(['operation', 'right']);

    $this->post('/calculator', ['operation' => 'invalid'])
        ->assertSessionHasErrors(['operation', 'right', 'left']);
});
