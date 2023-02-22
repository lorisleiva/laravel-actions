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
    $response = $this->postJson('/calculator', [
        'operation' => 'substraction',
        'left' => 5,
        'right' => 3,
    ]);

    // Then we receive a successful response.
    $response->assertOk()->assertExactJson([2]);
});

it('fails authorization', function () {
    // When we call that route with an unauthorized request.
    $response = $this->postJson('/calculator', [
        'operation' => 'unauthorized',
    ]);

    // Then we receive a forbidden error.
    $response->assertForbidden();
    $response->assertExactJson([
        'message' => 'This action is unauthorized.',
    ]);
});

it('fails validation', function () {
    // When we call that route with an invalid request.
    $response = $this->postJson('/calculator', [
        'operation' => 'multiplication',
        'left' => 'five',
    ]);

    // Then we receive a validation error.
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['operation', 'left', 'right']);
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
