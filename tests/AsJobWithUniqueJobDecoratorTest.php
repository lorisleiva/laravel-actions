<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

class AsJobWithUniqueJobDecoratorTest implements ShouldBeUnique
{
    use AsJob;

    public static ?int $latestResult;
    public static ?JobDecorator $latestJobDecorator;

    public function handle(int $left, int $right): void
    {
        static::$latestResult = $left + $right;
    }

    public function asJob(UniqueJobDecorator $job, int $left, int $right): void
    {
        static::$latestJobDecorator = $job;
        $this->handle($left, $right);
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobWithUniqueJobDecoratorTest::$latestResult = null;
    AsJobWithUniqueJobDecoratorTest::$latestJobDecorator = null;
});

it('can access the UniqueJobDecorator instance from the asJob method', function () {
    // When we dispatch a job that expects a UniqueJobDecorator as a first argument.
    AsJobWithUniqueJobDecoratorTest::dispatch(1, 2);

    // Then it received the UniqueJobDecorator as its first argument.
    $job = AsJobWithUniqueJobDecoratorTest::$latestJobDecorator;
    expect($job)->toBeInstanceOf(UniqueJobDecorator::class);
    expect($job->getParameters())->toBe([1, 2]);
    expect(get_class($job->getAction()))->toBe(AsJobWithUniqueJobDecoratorTest::class);

    // And the received the expected result.
    expect(AsJobWithUniqueJobDecoratorTest::$latestResult)->toBe(3);
});
