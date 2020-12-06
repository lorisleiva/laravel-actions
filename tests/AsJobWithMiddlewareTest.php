<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithMiddlewareTest
{
    use AsJob;

    /** @var null|int|string */
    public static $latestResult;

    public function getJobMiddleware(): array
    {
        return [
            function (JobDecorator $job, $next) {
                list($operation) = $job->getParameters();

                if ($operation === 'middleware') {
                    return static::$latestResult = 'caught_by_middleware';
                }

                $next($job);
            },
        ];
    }

    public function handle(string $operation, $left, $right)
    {
        static::$latestResult = $operation === 'addition'
            ? $left + $right
            : $left - $right;
    }
}

beforeEach(function () {
    // Given we reset the latest result.
    AsJobWithMiddlewareTest::$latestResult = null;
});

it('can go past the middleware', function () {
    // When we dispatch a normal operation.
    AsJobWithMiddlewareTest::dispatch('addition', 1, 2);

    // Then we executed the handle method.
    expect(AsJobWithMiddlewareTest::$latestResult)->toBe(3);
});

it('can be caught by middleware', function () {
    // When we dispatch an operation that should be caught in the middleware
    AsJobWithMiddlewareTest::dispatch('middleware', 1, 2);

    // Then we did not execute the handle method.
    expect(AsJobWithMiddlewareTest::$latestResult)->toBe('caught_by_middleware');
});
