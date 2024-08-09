<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

class AsJobWithUniqueHintedAsJobDecoratorTest implements ShouldBeUnique
{
    use AsJob;

    public static ?int $latestResult;
    public static ?JobDecorator $latestJobDecorator;

    public function handle(int $left, int $right): void
    {
        static::$latestResult = $left + $right;
    }

    public function asJob(JobDecorator $job, int $left, int $right): void
    {
        static::$latestJobDecorator = $job;
        $this->handle($left, $right);
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobWithUniqueHintedAsJobDecoratorTest::$latestResult = null;
    AsJobWithUniqueHintedAsJobDecoratorTest::$latestJobDecorator = null;
});

it('can access the UniqueJobDecorator instance from the asJob method even when hinted as JobDecorator', function () {
    // When we dispatch a job that expects a UniqueJobDecorator as a first argument.
    AsJobWithUniqueHintedAsJobDecoratorTest::dispatch(1, 2);

    // Then it received the UniqueJobDecorator as its first argument.
    $job = AsJobWithUniqueHintedAsJobDecoratorTest::$latestJobDecorator;
    expect($job)->toBeInstanceOf(UniqueJobDecorator::class);
    expect($job->getParameters())->toBe([1, 2]);
    expect(get_class($job->getAction()))->toBe(AsJobWithUniqueHintedAsJobDecoratorTest::class);

    // And the received the expected result.
    expect(AsJobWithUniqueHintedAsJobDecoratorTest::$latestResult)->toBe(3);
});
