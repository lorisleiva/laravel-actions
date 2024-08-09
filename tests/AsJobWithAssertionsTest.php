<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;
use PHPUnit\Framework\ExpectationFailedException;

class AsJobWithAssertionsTest
{
    use AsJob;

    public static ?string $queue;

    public static function setQueue(?string $queue): void
    {
        self::$queue = $queue;
    }

    public function configureJob(JobDecorator $job): void
    {
        $job->onQueue(static::$queue);
    }

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();

    // And reset the queue between each test.
    AsJobWithAssertionsTest::$queue = null;
});

it('asserts an action has been pushed - success', function () {
    // When we dispatch the action.
    AsJobWithAssertionsTest::dispatch();

    // Then we can assert it has been dispatched.
    AsJobWithAssertionsTest::assertPushed();
})->with('custom job decorators');

it('asserts an action has been pushed - failure', function () {
    // Given we don't dispatch the action.
    // ...

    // Then we fail the expectation.
    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('The expected ['.AsJobWithAssertionsTest::class.'] job was not pushed');

    // When we assert that it was pushed.
    AsJobWithAssertionsTest::assertPushed();
})->with('custom job decorators');

it('asserts an action has not been pushed - success', function () {
    // When we don't dispatch the action.
    // ...

    // Then we can assert it has not been dispatched.
    AsJobWithAssertionsTest::assertNotPushed();
})->with('custom job decorators');

it('asserts an action has not been pushed - failure', function () {
    // Given we dispatched the action.
    AsJobWithAssertionsTest::dispatch();

    // Then we fail the expectation.
    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('The unexpected ['.AsJobWithAssertionsTest::class.'] job was pushed');

    // When we assert that it was not pushed.
    AsJobWithAssertionsTest::assertNotPushed();
})->with('custom job decorators');

it('asserts an action has been pushed a given amount of times - success', function () {
    // When we dispatch the action twice.
    AsJobWithAssertionsTest::dispatch();
    AsJobWithAssertionsTest::dispatch();

    // Then we can assert it has been dispatched.
    AsJobWithAssertionsTest::assertPushed(2);
})->with('custom job decorators');

it('asserts an action has been pushed a given amount of times - failure', function () {
    // Given we dispatched the action twice.
    AsJobWithAssertionsTest::dispatch();
    AsJobWithAssertionsTest::dispatch();

    // Then we fail the expectation.
    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('The expected ['.AsJobWithAssertionsTest::class.'] job was pushed 2 times instead of 3 times');

    // When we assert that it was pushed 3 times.
    AsJobWithAssertionsTest::assertPushed(3);
})->with('custom job decorators');

it('asserts an action has been pushed on a given queue - success', function () {
    // When we dispatch the action on "some-queue".
    AsJobWithAssertionsTest::setQueue('some-queue');
    AsJobWithAssertionsTest::dispatch();

    // Then we can assert it has been dispatched on that queue.
    AsJobWithAssertionsTest::assertPushedOn('some-queue');
})->with('custom job decorators');

it('asserts an action has been pushed on a given queue - failure', function () {
    // Given we dispatched the action on "some-queue".
    AsJobWithAssertionsTest::setQueue('some-queue');
    AsJobWithAssertionsTest::dispatch();

    // Then we fail the expectation.
    $this->expectException(ExpectationFailedException::class);
    $this->expectExceptionMessage('The expected ['.AsJobWithAssertionsTest::class.'] job was not pushed');

    // When we pushed it on some other queue.
    AsJobWithAssertionsTest::assertPushedOn('some-other-queue');
})->with('custom job decorators');
