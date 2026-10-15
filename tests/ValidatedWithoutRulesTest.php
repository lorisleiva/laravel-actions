<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\WithAttributes;

/**
 * This action uses WithAttributes, so laravel-actions fills and validates
 * the action's own attributes rather than letting ActionRequest run its
 * built-in FormRequest validation. That means $request->validator is never
 * populated, yet the handler below still calls $request->validated()
 * directly on it, which is a valid thing for a consumer to do (see
 * https://github.com/lorisleiva/laravel-actions/issues/322).
 */
class ValidatedWithoutRulesTest
{
    use AsController;
    use WithAttributes;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function handle(ActionRequest $request): array
    {
        $this->fillFromRequest($request)->validateAttributes();

        // $request->validator was never set because validation went
        // through the action's own attributes instead.
        return [
            'name' => $this->name,
            'extra' => $request->validated('extra', 'fallback'),
        ];
    }
}

it('does not throw when validated() is called on a request with no validator set', function () {
    // Given the action is registered as a controller.
    Route::post('/greet', ValidatedWithoutRulesTest::class);

    // When we call that route with valid attributes and the handler calls
    // $request->validated() even though the request's own validator was
    // never populated.
    $response = $this->postJson('/greet', ['name' => 'Ada']);

    // Then it returns the given default instead of throwing on a null
    // validator.
    $response->assertOk();
    $response->assertExactJson(['name' => 'Ada', 'extra' => 'fallback']);
});
