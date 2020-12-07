<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithCustomRedirectAndErrorBagTest
{
    use AsController;

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

    public function getValidationRedirect(UrlGenerator $url): string
    {
        return $url->to('/my-custom-redirect-url');
    }

    public function getValidationErrorBag(): string
    {
        return 'my_custom_error_bag';
    }
}

beforeEach(function () {
    // Given an action with a custom validation redirect
    // and error bag registered as a controller.
    Route::post('/controller', AsControllerWithCustomRedirectAndErrorBagTest::class);
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

it('throws a validation exception with the custom redirect url and error bag', function () {
    // Given we disable exception handling for the test.
    $this->withoutExceptionHandling();

    // When we provide invalid data.
    try {
        $this->post('/controller');
    }

    // Then we received a ValidationException with our
    // custom redirect URL and our custom error bag.
    catch (ValidationException $exception) {
        expect($exception->redirectTo)->toBe(url('/my-custom-redirect-url'));
        expect($exception->errorBag)->toBe('my_custom_error_bag');

        return;
    }

    // Otherwise we failed to expect
    $this->fail('Expected a ValidationException');
});
