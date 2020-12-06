<?php

namespace Lorisleiva\Actions\Tests;

use Carbon\Carbon;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

class AsUniqueJobTest
{
    use AsJob;

    /** @var Repository */
    public static $cache;

    public function handle(int $id = 1)
    {
        //
    }

    public function getJobUniqueId(int $id = 1)
    {
        return $id;
    }

    public function getJobUniqueVia()
    {
        return static::$cache = Cache::driver('array');
    }

    public function getJobUniqueFor()
    {
        return 60; // 60 seconds.
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();

    // And reset the cache.
    AsUniqueJobTest::$cache = null;
});

it('dispatches multiple unique jobs once', function () {
    // When we dispatch two unique jobs with the same id.
    AsUniqueJobTest::dispatch(42);
    AsUniqueJobTest::dispatch(42);

    // Then we have dispatched it only once.
    Queue::assertPushed(UniqueJobDecorator::class, 1);
    Queue::assertPushed(UniqueJobDecorator::class, function (JobDecorator $job) {
        return $job instanceof ShouldBeUnique;
    });
});

it('dispatches unique jobs with different ids multiple times', function () {
    // When we dispatch two unique jobs with different ids.
    AsUniqueJobTest::dispatch(10);
    AsUniqueJobTest::dispatch(20);

    // Then we dispatched all two of them.
    Queue::assertPushed(UniqueJobDecorator::class, 2);
});

it('makes unique jobs by default when a unique id is provided', function () {
    // When we make a job from the action.
    $job = AsUniqueJobTest::makeJob();

    // Then it returns a UniqueJobDecorator.
    expect($job)->toBeInstanceOf(UniqueJobDecorator::class);
    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
});

it('caches job unique ids using the driver provided in getJobUniqueVia', function () {
    // When we dispatch a job that uses an array driver to cache job ids.
    AsUniqueJobTest::dispatch();

    // Then we used a ArrayStore under the hood to cache the job ids.
    expect(AsUniqueJobTest::$cache->getStore())
        ->toBeInstanceOf(ArrayStore::class);
});

it('caches job unique ids for the amount of seconds provided in getJobUniqueFor', function () {
    // When we dispatch a job that caches job unique ids for 1 minute.
    Carbon::setTestNow($now = now());
    AsUniqueJobTest::dispatch(42);

    // And another one 2 minutes later.
    Carbon::setTestNow($now->addMinutes(2));
    AsUniqueJobTest::dispatch(42);

    // Then we have dispatched both of these jobs.
    Queue::assertPushed(UniqueJobDecorator::class, 2);
});
