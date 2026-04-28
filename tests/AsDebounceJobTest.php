<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\DebounceJobDecorator;

#[DebounceFor(30)]
class AsDebounceJobTest
{
    use AsJob;

    public static ?Repository $cache;

    public function handle(int $id = 1): void
    {
        //
    }

    public function getJobDebounceId(int $id = 1): int
    {
        return $id;
    }

    public function getJobDebounceVia(): Repository
    {
        return static::$cache = Cache::driver('array');
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();

    // And reset the cache.
    AsDebounceJobTest::$cache = null;
});

it('makes debounce jobs by default when the action has the DebounceFor attribute', function () {
    // When we make a job from the action.
    $job = AsDebounceJobTest::makeJob();

    // Then it returns a DebounceJobDecorator.
    expect($job)->toBeInstanceOf(DebounceJobDecorator::class);
});

it('forwards the debounceFor value from the action attribute to the decorator', function () {
    // When we make a job from the action.
    $job = AsDebounceJobTest::makeDebounceJob();

    // Then the debounceFor property is set from the action's DebounceFor attribute.
    expect($job->debounceFor)->toBe(30);
});

it('forwards the debounce id from the action', function () {
    // When we make a job from the action with a specific id.
    $job = AsDebounceJobTest::makeJob(42);

    // Then the debounceId reflects the action parameter.
    expect($job->debounceId())->toContain('42');
});

it('uses the cache driver provided in getJobDebounceVia', function () {
    // When we dispatch a debounced job.
    AsDebounceJobTest::dispatch();

    // Then the action's debounce cache driver was used.
    expect(AsDebounceJobTest::$cache)->not->toBeNull();
});

it('can be tested using the assertPushed helper method', function () {
    // When we dispatch a debounced job.
    AsDebounceJobTest::dispatch(10);

    // Then we can assert it has been pushed using helper methods.
    AsDebounceJobTest::assertPushed();
});

it('dispatches a debounced job as a DebounceJobDecorator', function () {
    // When we dispatch a debounced job.
    AsDebounceJobTest::dispatch(42);

    // Then it is pushed to the queue as a DebounceJobDecorator.
    Queue::assertPushed(DebounceJobDecorator::class, function (DebounceJobDecorator $job) {
        return $job->debounceFor === 30;
    });
});
