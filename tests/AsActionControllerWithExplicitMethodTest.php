<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class AsActionControllerWithExplicitMethodTest
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
    // When we call that route.
    $response = $this->get(action_route(AsActionControllerWithExplicitMethodTest::class));

    // Then we expect to see the result of the default method: asController.
    $response->assertSee('as controller');
});

it('uses the explicit method when one is provided', function () {
    // When we call that route.
    $path = AsActionControllerWithExplicitMethodTest::class . '@myExplicitMethod';
    $response = $this->get(action_route($path));

    // Then we expect to see the result of the explicit method.
    $response->assertSee('my explicit method');
});

it('uses the explicit method when one is provided using array', function () {
    // When we call that route.
    $path = [AsActionControllerWithExplicitMethodTest::class, 'myExplicitMethod'];
    $response = $this->get(action_route($path));

    // Then we expect to see the result of the explicit method.
    $response->assertSee('my explicit method');
});

it('does not resolve authorization and validation when using explicit methods', function () {
    $path = AsActionControllerWithExplicitMethodTest::class . '@myExplicitMethod';
    $response = $this->post(action_route($path, ['operation' => 'unauthorized']));

    // Then authorization did not fail.
    $response->assertOk();
    $response->assertSee('my explicit method');
});

it('does resolve authorization and validation when using the default asController method', function () {
    $path = AsActionControllerWithExplicitMethodTest::class;
    $response = $this->post(action_route($path, ['operation' => 'unauthorized']));

    // Then authorization did not fail.
    $response->assertForbidden();
});
