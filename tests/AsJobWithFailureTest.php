<?php

namespace Lorisleiva\Actions\Tests;

use Exception;
use Lorisleiva\Actions\Concerns\AsJob;
use Throwable;

class AsJobWithFailureTest
{
    use AsJob;

    /** @var null|int|string */
    public static $latestResult;

    /** @var string|null */
    public static $latestError;

    /**
     * @throws Exception
     */
    public function handle(bool $throwException, ?string $errorMessage = null): void
    {
        static::$latestResult = 'started';

        if ($throwException) {
            throw new Exception();
        }

        static::$latestResult = 'completed';
    }

    public function jobFailed(Throwable $e, bool $throwException, string $errorMessage): void
    {
        static::$latestResult = 'exception_thrown';
        static::$latestError = $errorMessage;
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobWithFailureTest::$latestResult = null;
    AsJobWithFailureTest::$latestError = null;
});

it('calls the jobFailed function when the job fails', function () {
    try {
        // When we dispatch the action whilst telling it to throw an exception.
        AsJobWithFailureTest::dispatch(true, 'something went wrong');
    } catch (Throwable) {
        // Then an exception was thrown and the jobFailed method was executed.
        expect(AsJobWithFailureTest::$latestResult)->toBe('exception_thrown');
        expect(AsJobWithFailureTest::$latestError)->toBe('something went wrong');

        return;
    }

    // Otherwise, we fail because we did not throw an exception.
    test()->fail('The job should have failed by throwing an exception.');
});

it('does not call the jobFailed function when the job succeeds', function () {
    // When we dispatch the action whilst telling it to succeed.
    AsJobWithFailureTest::dispatch(false);

    // Then no exception was thrown and the jobFailed method was not executed.
    expect(AsJobWithFailureTest::$latestResult)->toBe('completed');
    expect(AsJobWithFailureTest::$latestError)->toBeNull();
});
