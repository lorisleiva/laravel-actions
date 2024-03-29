<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerWithExplicitMethodTest
{
    use AsController;

    public function authorize(Request $request): bool
    {
        return $request->get('operation') !== 'unauthorized';
    }

    public function asController(): string
    {
        return 'as controller';
    }

    public function myExplicitMethod(): string
    {
        return 'my explicit method';
    }
}

it('uses the asController method by default', function () {
    // Given we register the controller without an explicit method.
    Route::get('/controller', AsControllerWithExplicitMethodTest::class);

    // When we call that route.
    $response = $this->get('/controller');

    // Then we expect to see the result of the default method: asController.
    $response->assertSee('as controller');
});

it('uses the explicit method when one is provided', function () {
    // Given we register the controller with an explicit method.
    Route::get('/controller', [AsControllerWithExplicitMethodTest::class, 'myExplicitMethod']);

    // When we call that route.
    $response = $this->get('/controller');

    // Then we expect to see the result of the explicit method.
    $response->assertSee('my explicit method');
});

it('does not resolve authorization and validation when using explicit methods', function () {
    // Given we register the controller with an explicit method.
    Route::get('/controller', [AsControllerWithExplicitMethodTest::class, 'myExplicitMethod']);

    // When we call that route using a query parameter that should fail authorization.
    $response = $this->get('/controller?operation=unauthorized');

    // Then authorization did not fail.
    $response->assertOk();
    $response->assertSee('my explicit method');
});
