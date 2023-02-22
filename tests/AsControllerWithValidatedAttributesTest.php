<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\WithAttributes;

class AsControllerWithValidatedAttributesTest
{
    use AsController;
    use WithAttributes;

    public static bool $reachedHandleMethod = false;

    public function authorize(): bool
    {
        return $this->operation !== 'unauthorized';
    }

    public function rules(): array
    {
        return [
            'operation' => ['in:addition,substraction'],
            'left' => ['required', 'integer'],
            'right' => ['required', 'integer'],
        ];
    }

    public function handle(ActionRequest $request): array
    {
        static::$reachedHandleMethod = true;
        $this->fillFromRequest($request)->validateAttributes();

        $result = $this->operation === 'addition'
            ? $this->left + $this->right
            : $this->left - $this->right;

        return compact('result');
    }
}

beforeEach(function () {
    // Given the action is registered as a controller.
    Route::post('/calculator', AsControllerWithValidatedAttributesTest::class);

    // And given we reset the static variables between each test.
    AsControllerWithValidatedAttributesTest::$reachedHandleMethod = true;
});

it('passes authorization and validation', function () {
    // When we call that route with valid attributes.
    $response = $this->postJson('/calculator', [
        'operation' => 'substraction',
        'left' => 5,
        'right' => 3,
    ]);

    // Then we receive a successful response.
    $response->assertOk()->assertExactJson(['result' => 2]);
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
    $response->assertJsonValidationErrors([
        'operation' => 'The selected operation is invalid.',
        'left' => 'The left field must be an integer.',
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

it('does not validate from the request when using attributes', function () {
    // When we call that route with an unauthorized request.
    $response = $this->postJson('/calculator', [
        'operation' => 'unauthorized',
    ]);

    // Then we receive a forbidden error.
    $response->assertForbidden();

    // But we still reached the handle method which would not have
    // happened if we resolved the ActionRequest validation.
    expect(AsControllerWithValidatedAttributesTest::$reachedHandleMethod)
        ->toBeTrue();
});
