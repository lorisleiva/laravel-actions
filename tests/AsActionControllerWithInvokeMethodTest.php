<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Concerns\AsController;

class AsActionControllerWithInvokeMethodTest
{
    use AsController;

    public function authorize(Request $request): bool
    {
        return $request->get('operation') !== 'unauthorized';
    }

    public function __invoke(): string
    {
        return 'as invoke';
    }
}

it('uses the __invoke method by default', function () {
    // When we call that route.
    $response = $this->get(action_route(AsActionControllerWithInvokeMethodTest::class));

    // Then we expect to see the result of the default method: __invoke.
    $response->assertSee('as invoke');
});

it('does not resolve authorization and validation when using invoke method', function () {
    $path = AsActionControllerWithInvokeMethodTest::class;
    $response = $this->get(action_route($path, ['operation' => 'unauthorized']));

    // Then authorization did not fail.
    $response->assertOk();
    $response->assertSee('as invoke');
});
