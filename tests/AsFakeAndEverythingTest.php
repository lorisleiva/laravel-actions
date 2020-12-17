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

    public function handle(int $left, int $right): int
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

it('dummy', function () {
    $this->withoutExceptionHandling();

    // Given
    // Route::get('/controller/{left}/{right}', AsFakeAndEverythingTest::class);

    // Then
    AsFakeAndEverythingTest::shouldRun()
        ->once()
        ->with(1, 2)
        ->andReturn(42);

    // When
    // $this->getJson('controller/1/2')
    //     ->assertOk();
    AsFakeAndEverythingTest::dispatchNow(1, 2);
});
