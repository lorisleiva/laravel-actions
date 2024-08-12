<?php

namespace Lorisleiva\Actions\Tests;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobConfiguredWithMethodsTest
{
    use AsJob;

    public function configureJob(JobDecorator $job): void
    {
        $job->onConnection('my_connection')
            ->onQueue('my_queue')
            ->through(['my_middleware'])
            ->chain(['my_chain'])
            ->delay(60)
            ->setDeleteWhenMissingModels(true);
    }

    public function getJobBackoff(): array
    {
        return [30, 60, 120];
    }

    public function getJobRetryUntil(): DateTime
    {
        return now()->addMinutes(30);
    }

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('uses the configuration provided in the configureJob and getJobX methods', function () {
    // Given we stop time for the test.
    Carbon::setTestNow();

    // When we dispatch the configured job.
    AsJobConfiguredWithMethodsTest::dispatch();

    // Then it was pushed to the queue using the given configurations.
    assertJobPushed(AsJobConfiguredWithMethodsTest::class, function (JobDecorator $job) {
        return $job->connection === 'my_connection'
            && $job->queue === 'my_queue'
            && $job->middleware === ['my_middleware']
            && $job->chained === [serialize('my_chain')]
            && $job->delay === 60
            && $job->deleteWhenMissingModels === true
            && $job->backoff() === [30, 60, 120]
            && $job->retryUntil()->timestamp === now()->addMinutes(30)->timestamp;
    });
});
