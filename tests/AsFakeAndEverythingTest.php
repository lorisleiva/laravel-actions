<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Concerns\AsAction;

class AsFakeAndEverythingTest
{
    use AsAction;

    public static int $constructed = 0;
    public static int $handled = 0;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle($left, $right)
    {
        static::$handled++;

        return $left + $right;
    }
}

beforeEach(function () {
    // Given we reset the static counters between each test.
    AsFakeAndEverythingTest::$constructed = 0;
    AsFakeAndEverythingTest::$handled = 0;
});

it('can mock actions as controllers', function () {
    // Given we have the following mock expectations.
    AsFakeAndEverythingTest::shouldRun()
        ->once()
        ->with(1, 2)
        ->andReturn('Forty-two');

    // And a route registered.
    Route::get('/controller/{left}/{right}', AsFakeAndEverythingTest::class);

    // When we call the route with the expected arguments.
    $response = $this->get('controller/1/2');

    // Then we get the expected response.
    $response->assertOk();
    $response->assertSee('Forty-two');

    // And the handle method did not run.
    expect(AsFakeAndEverythingTest::$constructed)->toBe(1);
    expect(AsFakeAndEverythingTest::$handled)->toBe(0);
});

it('can mock actions as jobs', function () {
    // Given we have the following mock expectations.
    AsFakeAndEverythingTest::shouldRun()
        ->once()
        ->with(1, 2);

    // When we dispatch the mocked aciton as a job.
    AsFakeAndEverythingTest::dispatchNow(1, 2);

    // Then the handle method did not run.
    expect(AsFakeAndEverythingTest::$constructed)->toBe(2);
    expect(AsFakeAndEverythingTest::$handled)->toBe(0);
});
