<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithConfigurePropertiesTest
{
    use AsJob;

    public string $jobConnection = 'my_connection';
    public string $jobQueue = 'my_queue';

    public function handle()
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('uses the configuration provided in properties', function () {
    // When we dispatch the configured job.
    AsJobWithConfigurePropertiesTest::dispatch();

    // Then it was pushed to the queue using the given configurations.
    assertJobPushed(AsJobWithConfigurePropertiesTest::class, function (JobDecorator $job) {
        return $job->connection === 'my_connection'
            && $job->queue === 'my_queue';
    });
});
