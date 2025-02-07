<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Routing\Middleware\HandlePrecognitiveActionRequests;

class AsControllerWithPrecognitionTest
{
    use AsController, AsFake;

    public function authorize(ActionRequest $request): bool
    {
        return $request->header('auth') !== 'unauthorized';
    }

    public function rules(): array
    {
        return [
            'requiredString' => ['required', 'string'],
            'optionalNumber' => ['numeric'],
        ];
    }

    public function handle(ActionRequest $request)
    {
        return $request->get('requiredString');
    }
}

beforeEach(function () {
    Route::post('/normal', AsControllerWithPrecognitionTest::class);
    Route::middleware(HandlePrecognitiveActionRequests::class)->post(
        '/precognition',
        AsControllerWithPrecognitionTest::class,
    );
});

it('correctly handles successful precognitive requests', function () {
    AsControllerWithPrecognitionTest::partialMock()->shouldNotReceive('handle');
    $request = $this->withHeader('Precognition', 'true')
        ->postJson('/precognition', ['requiredString' => 'test'])
        ->assertNoContent()
        ->assertHeader('Precognition', true);
    // only present on Laravel 10.11.0+ (#47081)
    if (version_compare(app()->version(), '10.11.0', '>='))
        $request->assertHeader('Precognition-Success', true);
});

it('correctly handles successful precognitive validate only requests', function () {
    AsControllerWithPrecognitionTest::partialMock()->shouldNotReceive('handle');
    $request = $this->withHeader('Precognition', 'true')
        ->withHeader('Precognition-Validate-Only', 'optionalNumber')
        ->postJson('/precognition', ['optionalNumber' => '5'])
        ->assertNoContent()
        ->assertHeader('Precognition', true);
    // only present on Laravel 10.11.0+ (#47081)
    if (version_compare(app()->version(), '10.11.0', '>='))
        $request->assertHeader('Precognition-Success', true);
});

it('correctly handles unsuccessful precognitive requests', function () {
    AsControllerWithPrecognitionTest::partialMock()->shouldNotReceive('handle');
    $this->withHeader('Precognition', 'true')
        ->postJson('/precognition', ['optionalNumber' => 'NaN'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('requiredString')
        ->assertJsonValidationErrorFor('optionalNumber')
        ->assertHeader('Precognition', true)
        ->assertHeaderMissing('Precognition-Success');
});

it('correctly handles unsuccessful precognitive validate only requests', function () {
    AsControllerWithPrecognitionTest::partialMock()->shouldNotReceive('handle');
    $this->withHeader('Precognition', 'true')
        ->withHeader('Precognition-Validate-Only', 'optionalNumber')
        ->postJson('/precognition', ['optionalNumber' => 'NaN'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('optionalNumber')
        ->assertJsonMissingValidationErrors('requiredString')
        ->assertHeader('Precognition', true)
        ->assertHeaderMissing('Precognition-Success');
});

it('does not mistakenly make non-precognition actions precognitive', function () {
    $string = fake()->text();
    $this->withHeader('Precognition', 'true')
        ->postJson('/normal', ['requiredString' => $string, 'optionalNumber' => '5'])
        ->assertContent($string)
        ->assertOk()
        ->assertHeaderMissing('Precognition')
        ->assertHeaderMissing('Precognition-Success');
});

it('does not mistakenly make non-precognition actions precognitive with validate only', function () {
    $this->withHeader('Precognition', 'true')
        ->withHeader('Precognition-Validate-Only', 'optionalNumber')
        ->postJson('/normal', ['optionalNumber' => 'NaN'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('optionalNumber')
        ->assertJsonValidationErrorFor('requiredString')
        ->assertHeaderMissing('Precognition')
        ->assertHeaderMissing('Precognition-Success');
});
