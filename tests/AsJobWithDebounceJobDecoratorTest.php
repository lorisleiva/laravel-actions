<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\DebounceJobDecorator;
use Lorisleiva\Actions\Decorators\JobDecorator;

#[DebounceFor(30)]
class AsJobWithDebounceJobDecoratorTest
{
    use AsJob;

    public static ?int $latestResult;
    public static ?JobDecorator $latestJobDecorator;

    public function handle(int $left, int $right): void
    {
        static::$latestResult = $left + $right;
    }

    public function asJob(DebounceJobDecorator $job, int $left, int $right): void
    {
        static::$latestJobDecorator = $job;
        $this->handle($left, $right);
    }

    public function getJobDebounceVia(): Repository
    {
        return Cache::driver('array');
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobWithDebounceJobDecoratorTest::$latestResult = null;
    AsJobWithDebounceJobDecoratorTest::$latestJobDecorator = null;
});

it('can access the DebounceJobDecorator instance from the asJob method', function () {
    // When we dispatch a job that expects a DebounceJobDecorator as a first argument.
    AsJobWithDebounceJobDecoratorTest::dispatch(1, 2);

    // Then it received the DebounceJobDecorator as its first argument.
    $job = AsJobWithDebounceJobDecoratorTest::$latestJobDecorator;
    expect($job)->toBeInstanceOf(DebounceJobDecorator::class);
    expect($job->getParameters())->toBe([1, 2]);
    expect(get_class($job->getAction()))->toBe(AsJobWithDebounceJobDecoratorTest::class);

    // And it received the expected result.
    expect(AsJobWithDebounceJobDecoratorTest::$latestResult)->toBe(3);
});
