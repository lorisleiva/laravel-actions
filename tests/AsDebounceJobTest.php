<?php

namespace Lorisleiva\Actions\Tests;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use LogicException;
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

#[DebounceFor(30, maxWait: 60)]
class AsDebounceJobWithMaxWaitTest
{
    use AsJob;

    public function handle(): void
    {
        //
    }

    public function getJobDebounceVia(): Repository
    {
        return Cache::driver('array');
    }
}

#[DebounceFor(30, maxWait: 60)]
class AsDebounceJobWithDebounceForMethodTest
{
    use AsJob;

    public function handle(int $seconds): void
    {
        //
    }

    public function getJobDebounceFor(int $seconds): int
    {
        return $seconds;
    }
}

#[DebounceFor(30)]
class AsDebounceJobThatIsAlsoUniqueTest implements ShouldBeUnique
{
    use AsJob;

    public function handle(): void
    {
        //
    }
}

#[DebounceFor(45)]
abstract class AsDebounceJobBaseTest
{
    use AsJob;
}

class AsDebounceJobInheritingFromParentTest extends AsDebounceJobBaseTest
{
    public function handle(): void
    {
        //
    }
}

#[DebounceFor(15)]
trait AsDebounceJobTraitTest
{
    //
}

class AsDebounceJobInheritingFromTraitTest
{
    use AsJob;
    use AsDebounceJobTraitTest;

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    // Debounced jobs are only available on Laravel 13.6+.
    if (! class_exists(DebounceFor::class)) {
        test()->markTestSkipped('Debounced jobs require Laravel 13.6+.');
    }

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
    // When we make a debounce job from the action.
    $job = AsDebounceJobTest::makeDebounceJob();

    // Then the debounceFor property is set from the action's DebounceFor attribute.
    expect($job->debounceFor)->toBe(30);
});

it('forwards the maxWait value from the action attribute to the decorator', function () {
    // When we make a debounce job from an action whose attribute defines maxWait.
    $job = AsDebounceJobWithMaxWaitTest::makeDebounceJob();

    // Then both debounceFor and maxWait are forwarded.
    expect($job->debounceFor)->toBe(30);
    expect($job->maxWait)->toBe(60);
});

it('uses getJobDebounceFor over the attribute and keeps its maxWait', function () {
    // When we make a debounce job from an action whose getJobDebounceFor method uses its parameters.
    $job = AsDebounceJobWithDebounceForMethodTest::makeDebounceJob(5);

    // Then debounceFor comes from the method and maxWait from the attribute.
    expect($job->debounceFor)->toBe(5);
    expect($job->maxWait)->toBe(60);
});

it('forwards the debounce id from the action', function () {
    // When we make a debounce job from the action with a specific id.
    $job = AsDebounceJobTest::makeDebounceJob(42);

    // Then the debounceId reflects the action parameter.
    expect($job->debounceId())->toBe('42');
});

it('makes debounce jobs when the DebounceFor attribute is declared on a parent class', function () {
    // When we make a job from an action whose parent class has the DebounceFor attribute.
    $job = AsDebounceJobInheritingFromParentTest::makeJob();
    $debounceJob = AsDebounceJobInheritingFromParentTest::makeDebounceJob();

    // Then it returns a DebounceJobDecorator using the inherited debounceFor value.
    expect($job)->toBeInstanceOf(DebounceJobDecorator::class);
    expect($debounceJob->debounceFor)->toBe(45);
});

it('makes debounce jobs when the DebounceFor attribute is declared on a trait', function () {
    // When we make a job from an action using a trait that has the DebounceFor attribute.
    $job = AsDebounceJobInheritingFromTraitTest::makeJob();
    $debounceJob = AsDebounceJobInheritingFromTraitTest::makeDebounceJob();

    // Then it returns a DebounceJobDecorator using the trait's debounceFor value.
    expect($job)->toBeInstanceOf(DebounceJobDecorator::class);
    expect($debounceJob->debounceFor)->toBe(15);
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

it('cannot be both unique and debounced', function () {
    // When we try to dispatch an action that is both unique and debounced
    // Then a LogicException is thrown, matching Laravel's own behaviour.
    expect(fn () => AsDebounceJobThatIsAlsoUniqueTest::dispatch())
        ->toThrow(LogicException::class, 'A debounced job cannot also implement ShouldBeUnique.');
});

it('dispatches a debounced job as a DebounceJobDecorator', function () {
    // When we dispatch a debounced job.
    AsDebounceJobTest::dispatch(42);

    // Then it is pushed to the queue as a DebounceJobDecorator.
    Queue::assertPushed(DebounceJobDecorator::class, function (DebounceJobDecorator $job) {
        return $job->debounceFor === 30;
    });
});

it('acquires the debounce lock and delays the job by debounceFor', function () {
    // When we dispatch a debounced job.
    AsDebounceJobTest::dispatch(42);

    // Then it was pushed with a debounce owner and delayed by debounceFor.
    Queue::assertPushed(DebounceJobDecorator::class, function (DebounceJobDecorator $job) {
        return $job->debounceOwner !== '' && $job->delay === 30;
    });
});

it('dispatches without delay once maxWait is exceeded', function () {
    // When we dispatch a job whose attribute defines a maxWait of 60 seconds.
    Carbon::setTestNow($now = now());
    AsDebounceJobWithMaxWaitTest::dispatch();

    // And dispatch it again once maxWait has elapsed.
    Carbon::setTestNow($now->copy()->addSeconds(61));
    AsDebounceJobWithMaxWaitTest::dispatch();

    // Then the first job is delayed by debounceFor and the second is not delayed.
    Queue::assertPushed(DebounceJobDecorator::class, fn (DebounceJobDecorator $job) => $job->delay === 30);
    Queue::assertPushed(DebounceJobDecorator::class, fn (DebounceJobDecorator $job) => $job->delay === 0);
});
