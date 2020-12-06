<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithConfigureMethodTest
{
    use AsJob;

    public function configureJob(JobDecorator $job)
    {
        $job->onConnection('my_connection')
            ->onQueue('my_queue')
            ->through(['my_middleware'])
            ->chain(['my_chain'])
            ->delay(60);
    }

    public function handle()
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('uses the configuration provided in the configureJob method', function () {
    // When we dispatch the configured job.
    AsJobWithConfigureMethodTest::dispatch();

    // Then it was pushed to the queue using the given configurations.
    assertJobPushed(AsJobWithConfigureMethodTest::class, function (JobDecorator $job) {
        return $job->connection === 'my_connection'
            && $job->queue === 'my_queue'
            && $job->middleware === ['my_middleware']
            && $job->chained === [serialize('my_chain')]
            && $job->delay === 60;
    });
});
