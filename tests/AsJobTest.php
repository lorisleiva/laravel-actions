<?php

namespace Lorisleiva\Actions\Tests;

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
    AsJobTest::dispatchNow();

    Queue::assertPushed(JobDecorator::class, function (JobDecorator $job) {
        return $job->getAction() instanceof AsJobTest;
    });
});

it('can be dispatched synchronously with parameters')->skip();
it('can be dispatched asynchronously')->skip();
it('can be dispatched asynchronously with parameters')->skip();
it('can be dispatched on a specific queue')->skip();
it('uses the decorated action as display name')->skip();
