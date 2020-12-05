<?php

namespace Lorisleiva\Actions\Tests;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobTest
{
    use AsJob;

    public function handle()
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('can be dispatched synchronously', function () {
    // When we dispatch the job now.
    AsJobTest::dispatchNow();

    // Then it was pushed to the queue using the "sync" connection.
    assertJobPushed(AsJobTest::class, function (JobDecorator $job) {
        return $job->connection === 'sync';
    });
});

it('can be dispatched synchronously with parameters', function () {
    // Given the following job parameters.
    $parameters = [1, 'two', new Filesystem()];

    // When we dispatch the job now with these parameters.
    AsJobTest::dispatchNow(...$parameters);

    // Then it was pushed to the queue with these parameters.
    assertJobPushedWith(AsJobTest::class, $parameters);
});

it('can be dispatched asynchronously', function () {
    // When we dispatch the job asynchronously.
    AsJobTest::dispatch();

    // Then it was pushed to the queue using the default connection.
    assertJobPushed(AsJobTest::class, function (JobDecorator $job) {
        return is_null($job->connection);
    });
});

it('can be dispatched asynchronously with parameters', function () {
    // Given the following job parameters.
    $parameters = [1, 'two', new Filesystem()];

    // When we dispatch the job now with these parameters.
    AsJobTest::dispatch(...$parameters);

    // Then it was pushed to the queue with these parameters.
    assertJobPushedWith(AsJobTest::class, $parameters);
});

it('can be dispatched with overridden configurations', function () {
    // When we dispatch the job with the following configurations.
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
    // When we dispatch the job after the response.
    $pendingJob = AsJobTest::dispatchAfterResponse();

    expect($pendingJob)->toBeInstanceOf(PendingDispatch::class);
});

it('uses the decorated action as display name')->skip();
