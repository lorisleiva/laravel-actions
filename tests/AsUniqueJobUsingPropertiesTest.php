<?php

namespace Lorisleiva\Actions\Tests;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

class AsUniqueJobUsingPropertiesTest
{
    use AsJob;

    public $jobUniqueId = 'my_job_id';

    public $jobUniqueFor = 120; // 2 minutes.

    public function handle()
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('dispatches multiple unique jobs once', function () {
    // When we dispatch two unique jobs with the same id.
    AsUniqueJobUsingPropertiesTest::dispatch();
    AsUniqueJobUsingPropertiesTest::dispatch();

    // Then we have dispatched it only once.
    Queue::assertPushed(UniqueJobDecorator::class, 1);
    Queue::assertPushed(UniqueJobDecorator::class, function (JobDecorator $job) {
        return $job instanceof ShouldBeUnique;
    });
});

it('makes unique jobs by default when a unique id is provided as a property', function () {
    // When we make a job from the action.
    $job = AsUniqueJobUsingPropertiesTest::makeJob();

    // Then it returns a UniqueJobDecorator.
    expect($job)->toBeInstanceOf(UniqueJobDecorator::class);
    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
});

it('caches job unique ids for the amount of seconds provided in the jobUniqueFor property', function () {
    // When we dispatch a job that caches job unique ids for 2 minutes.
    Carbon::setTestNow($now = now());
    AsUniqueJobUsingPropertiesTest::dispatch();

    // And another one 3 minutes later.
    Carbon::setTestNow($now->addMinutes(3));
    AsUniqueJobUsingPropertiesTest::dispatch();

    // Then we have dispatched both of these jobs.
    Queue::assertPushed(UniqueJobDecorator::class, 2);
});
