<?php

namespace Lorisleiva\Actions\Tests;

use Faker\Provider\File;
use Illuminate\Filesystem\Filesystem;
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

it('can be dispatched asynchronously')->skip();
it('can be dispatched asynchronously with parameters')->skip();
it('can be dispatched on a specific queue')->skip();
it('uses the decorated action as display name')->skip();
