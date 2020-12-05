<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithUniqueIdTest
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
    AsJobWithUniqueIdTest::dispatchNow(1);
    AsJobWithUniqueIdTest::dispatchNow(1);

    // Then
    Queue::assertPushed(JobDecorator::class, 1);
});
