<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithDeleteWhenMissingModelsMethodTest
{
    use AsJob;

    public bool $jobDeleteWhenMissingModels = false;

    public function getJobDeleteWhenMissingModels(bool $delete): bool
    {
        return $delete;
    }

    public function handle(bool $delete): void
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('uses the getJobDeleteWhenMissingModels method over the property', function () {
    // When we dispatch a job whose method says to delete it when models are missing.
    AsJobWithDeleteWhenMissingModelsMethodTest::dispatch(true);

    // Then the job is deleted when its models are missing.
    assertJobPushed(AsJobWithDeleteWhenMissingModelsMethodTest::class, function (JobDecorator $job) {
        return $job->deleteWhenMissingModels === true;
    });
});
