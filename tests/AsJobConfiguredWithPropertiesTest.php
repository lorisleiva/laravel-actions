<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobConfiguredWithPropertiesTest
{
    use AsJob;

    public string $jobConnection = 'my_connection';
    public string $jobQueue = 'my_queue';
    public int $jobTries = 10;
    public int $jobMaxExceptions = 3;
    public int $jobBackoff = 60 * 5;
    public int $jobTimeout = 60 * 30;
    public int $jobRetryUntil = 3600 * 2;
    public bool $jobDeleteWhenMissingModels = true;

    public function handle(): void
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
    AsJobConfiguredWithPropertiesTest::dispatch();

    // Then it was pushed to the queue using the given configurations.
    assertJobPushed(AsJobConfiguredWithPropertiesTest::class, function (JobDecorator $job) {
        return $job->connection === 'my_connection'
            && $job->queue === 'my_queue'
            && $job->tries === 10
            && $job->maxExceptions === 3
            && $job->backoff() === 60 * 5
            && $job->timeout === 60 * 30
            && $job->deleteWhenMissingModels === true
            && $job->retryUntil() === 3600 * 2;
    });
});
