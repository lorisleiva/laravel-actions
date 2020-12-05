<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

class AsUniqueJobTest
{
    use AsJob;

    public function handle(int $id = 1)
    {
        //
    }

    public function getJobUniqueId(int $id = 1)
    {
        return $id;
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('dispatches multiple unique jobs once', function () {
    // When we dispatch two unique jobs with the same id.
    AsUniqueJobTest::dispatch(1);
    AsUniqueJobTest::dispatch(1);

    // Then we have dispatched it only once.
    Queue::assertPushed(UniqueJobDecorator::class, 1);
    Queue::assertPushed(UniqueJobDecorator::class, function (JobDecorator $job) {
        return $job instanceof ShouldBeUnique;
    });
});

it('dispatches unique jobs with different ids multiple times', function () {
    // When we dispatch two unique jobs with different ids.
    AsUniqueJobTest::dispatch(1);
    AsUniqueJobTest::dispatch(2);

    // Then we dispatched all two of them.
    Queue::assertPushed(UniqueJobDecorator::class, 2);
});
