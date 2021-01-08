<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

class AsJobAndObjectTest
{
    use AsObject;
    use AsJob;

    public static ?int $latestResult;

    public function handle(int $left, int $right, bool $addition = true): int
    {
        return $addition ? $left + $right : $left - $right;
    }

    public function asJob(string $operation, int $left, int $right): void
    {
        static::$latestResult = $this->handle($left, $right, $operation === 'addition');
    }
}

beforeEach(function () {
    // Given we reset the latest result between tests.
    AsJobAndObjectTest::$latestResult = null;
});

it('works as an object', function () {
    // When we run the action as an object.
    $result = AsJobAndObjectTest::run(5, 3, false);

    // Then we get the expected result.
    expect($result)->toBe(2);

    // And the `asJob` method was not called.
    expect(AsJobAndObjectTest::$latestResult)->toBeNull();
});

it('works as a job', function () {
    // When we run the action as an object.
    AsJobAndObjectTest::dispatch('substraction', 5, 3);

    // Then we get the expected result.
    expect(AsJobAndObjectTest::$latestResult)->toBe(2);
});
