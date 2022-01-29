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

    public function handle(bool $throwException)
    {
		static::$latestResult = 'started';

		if ($throwException) {
			throw new Exception();
		}

		static::$latestResult = 'completed';

	}

	public function jobFailed(Throwable $e) {
		static::$latestResult = 'exception_thrown';
	}
}

it('asserts a job failure calls the jobFailed() function', function () {

	$this->expectException(Exception::class);

	// When we dispatch the action.
	AsJobWithFailureTest::dispatch(true);

	expect(AsJobWithFailureTest::$latestResult)->toBe('exception_thrown');

});

it('asserts no job failure does not call the jobFailed() function', function () {

	// When we dispatch the action.
	AsJobWithFailureTest::dispatch(false);

	expect(AsJobWithFailureTest::$latestResult)->toBe('completed');

});

