<?php

namespace Lorisleiva\Actions\Tests;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

class AsJobTest
{
    use AsJob;

    /** @var int */
    public static $constructed = 0;

    /** @var int */
    public static $handled = 0;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle()
    {
        static::$handled++;
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();

    // And reset the static counters.
    AsJobTest::$constructed = 0;
    AsJobTest::$handled = 0;
});

it('can be dispatched synchronously', function () {
    // When we dispatch a job now.
    AsJobTest::dispatchNow();

    // And it was pushed to the queue using the "sync" connection.
    assertJobPushed(AsJobTest::class, function (JobDecorator $job) {
        return $job->connection === 'sync';
    });
});

it('can be dispatched synchronously with parameters', function () {
    // Given the following job parameters.
    $parameters = [1, 'two', new Filesystem()];

    // When we dispatch a job now with these parameters.
    AsJobTest::dispatchNow(...$parameters);

    // Then it was pushed to the queue with these parameters.
    assertJobPushedWith(AsJobTest::class, $parameters);
});

it('can be dispatched asynchronously', function () {
    // When we dispatch a job asynchronously.
    AsJobTest::dispatch();

    // Then it was pushed to the queue using the default connection.
    assertJobPushed(AsJobTest::class, function (JobDecorator $job) {
        return is_null($job->connection);
    });
});

it('can be dispatched asynchronously with parameters', function () {
    // Given the following job parameters.
    $parameters = [1, 'two', new Filesystem()];

    // When we dispatch a job now with these parameters.
    AsJobTest::dispatch(...$parameters);

    // Then it was pushed to the queue with these parameters.
    assertJobPushedWith(AsJobTest::class, $parameters);
});

it('can make a job statically', function () {
    // Given the following job parameters.
    $parameters = [1, 'two', new Filesystem()];

    // When we make a job from that action with these parameters.
    $job = AsJobTest::makeJob(...$parameters);

    // And dispatch that job.
    dispatch($job);

    // Then the created job is a JobDecorator that kept track of the action and its paremeters.
    expect($job)->toBeInstanceOf(JobDecorator::class);
    expect($job->getAction())->toBeInstanceOf(AsJobTest::class);
    expect($job->getParameters())->toBe($parameters);

    // And it is not unique by default.
    expect($job)->not()->toBeInstanceOf(ShouldBeUnique::class);

    // And the job was dispatched to the queue.
    assertJobPushed(AsJobTest::class);
});

it('can make a unique job statically', function () {
    // When we make a unique job from the action.
    $job = AsJobTest::makeUniqueJob();

    // Then it returns a UniqueJobDecorator.
    expect($job)->toBeInstanceOf(UniqueJobDecorator::class);
    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
});

it('can be dispatched with overridden configurations', function () {
    // When we dispatch a job with the following configurations.
    AsJobTest::dispatch()
        ->onConnection('my_connection')
        ->onQueue('my_queue')
        ->delay($dispatchedTime = Carbon::now()->addMinutes(10));

    // Then it was pushed to the queue using these configurations.
    assertJobPushed(AsJobTest::class, function (JobDecorator $job) use ($dispatchedTime) {
        return $job->connection === 'my_connection'
            && $job->queue === 'my_queue'
            && $job->delay->getTimestamp() === $dispatchedTime->getTimestamp();
    });
});

it('can be dispatched after the response', function () {
    // When we dispatch a job after the response.
    AsJobTest::dispatchAfterResponse();

    // Then it is not dispatch immediately.
    expect(AsJobTest::$handled)->toBe(0);

    // But when the app terminates.
    app()->terminate();

    // Then the job was dispatched.
    expect(AsJobTest::$handled)->toBe(1);
});

it('constructs a new job at every dispatch', function () {
    // When we dispatch a job twice.
    AsJobTest::dispatchNow();
    AsJobTest::dispatchNow();

    // It has been initialised twice.
    expect(AsJobTest::$constructed)->toBe(2);
});

it('uses the decorated action as display name by default', function () {
    // When we dispatch a job.
    AsJobTest::dispatch();

    // Then its default display name is the classname of the decorated action.
    assertJobPushed(AsJobTest::class, function (JobDecorator $job) {
        return $job->displayName() === AsJobTest::class;
    });
});

it('can be dispatch conditionally', function () {
    AsJobTest::dispatchIf(true);
    Queue::assertPushed(JobDecorator::class, 1);

    AsJobTest::dispatchIf(false);
    Queue::assertPushed(JobDecorator::class, 1);

    AsJobTest::dispatchUnless(true);
    Queue::assertPushed(JobDecorator::class, 1);

    AsJobTest::dispatchUnless(false);
    Queue::assertPushed(JobDecorator::class, 2);
});

it('can be dispatched with a chain', function () {
    // When we dispatch a job with a chain.
    AsJobTest::withChain([
        AsJobTest::makeJob(2),
        AsJobTest::makeJob(3),
        AsJobTest::makeJob(4),
    ])->dispatch(1);

    // Then it has been dispatched with as a chain in the correct order.
    Queue::assertPushedWithChain(
        JobDecorator::class,
        [
            JobDecorator::class,
            JobDecorator::class,
            JobDecorator::class,
        ],
        function (JobDecorator $job) {
            if (! $job->getAction() instanceof AsJobTest || $job->getParameters() !== [1]) {
                return false;
            }

            foreach (array_map('unserialize', $job->chained) as $index => $chain) {
                if (! $chain->getAction() instanceof AsJobTest || $chain->getParameters() !== [$index + 2]) {
                    return false;
                }
            }

            return true;
        }
    );
});
