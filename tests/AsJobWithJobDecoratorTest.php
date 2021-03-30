<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithJobDecoratorTest
{
    use AsJob;

    public static ?int $latestResult;
    public static ?JobDecorator $latestJobDecorator;

    public function handle(int $left, int $right)
    {
        static::$latestResult = $left + $right;
    }

    public function asJob(JobDecorator $job, int $left, int $right)
    {
        static::$latestJobDecorator = $job;
        $this->handle($left, $right);
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobWithJobDecoratorTest::$latestResult = null;
    AsJobWithJobDecoratorTest::$latestJobDecorator = null;
});

it('can access the JobDecorator instance from the asJob method', function () {
    // When we dispatch a job that expects a JobDecorator as a first argument.
    AsJobWithJobDecoratorTest::dispatch(1, 2);

    // Then it received the JobDecorator as its first argument.
    $job = AsJobWithJobDecoratorTest::$latestJobDecorator;
    expect($job)->toBeInstanceOf(JobDecorator::class);
    expect($job->getParameters())->toBe([1, 2]);
    expect(get_class($job->getAction()))->toBe(AsJobWithJobDecoratorTest::class);

    // And the received the expected result.
    expect(AsJobWithJobDecoratorTest::$latestResult)->toBe(3);
});
