<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsUniqueJobTest
{
    use AsJob;

    /** @var int */
    protected $id;

    public function handle(int $id)
    {
        $this->id = $id;
    }

    public function getJobUniqueId()
    {
        return $this->id;
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();
});

it('todo', function () {
    // When
    AsUniqueJobTest::dispatchNow(1);
    AsUniqueJobTest::dispatchNow(1);

    // Then
    Queue::assertPushed(JobDecorator::class, 1);
    Queue::assertPushed(JobDecorator::class, function (JobDecorator $job) {
        return $job instanceof ShouldBeUnique;
    });
});
