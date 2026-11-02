<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\PreparesForDispatch;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Lorisleiva\Actions\Tests\Stubs\PreparesForDispatchAction;
use Lorisleiva\Actions\Tests\Stubs\PreparesForDispatchUniqueAction;

class AsJobPreparedForDispatchTest
{
    use AsJob;

    public static bool $prepared;

    public function handle(): void
    {
        //
    }

    public function prepareForDispatch(): bool
    {
        static::$prepared = true;

        return false;
    }
}

beforeEach(function () {
    // Preparing jobs for dispatch is only available on Laravel 13.
    if (! interface_exists(PreparesForDispatch::class)) {
        test()->markTestSkipped('Preparing jobs for dispatch requires Laravel 13.');
    }

    // Given we mock the queue driver.
    Queue::fake();

    // And reset the static variables.
    PreparesForDispatchAction::$preparedWith = [];
    AsJobPreparedForDispatchTest::$prepared = false;
});

it('prepares the action with the dispatched parameters before dispatching it', function () {
    // When we dispatch an action that prepares for dispatch.
    PreparesForDispatchAction::dispatch(42);

    // Then it was prepared with its parameters.
    expect(PreparesForDispatchAction::$preparedWith)->toBe([42]);

    // And it was dispatched.
    PreparesForDispatchAction::assertPushed(1);
});

it('cancels the dispatch when the action returns false', function () {
    // When we dispatch an action whose preparation returns false.
    PreparesForDispatchAction::dispatch(0);

    // Then it was not dispatched.
    PreparesForDispatchAction::assertNotPushed();
});

it('prepares unique jobs before taking their lock', function () {
    // When we dispatch a unique action whose preparation cancels the dispatch.
    PreparesForDispatchUniqueAction::dispatch(0);

    // And then dispatch it again with the same unique id.
    PreparesForDispatchUniqueAction::dispatch(42);

    // Then the cancelled dispatch didn't hold the lock, so the second one went through.
    Queue::assertPushed(UniqueJobDecorator::class, 1);
});

it('does not call prepareForDispatch on actions that do not implement PreparesForDispatch', function () {
    // When we dispatch an action that has the method but not the interface.
    AsJobPreparedForDispatchTest::dispatch();

    // Then it was not asked, and it was dispatched, just as Laravel treats a native job.
    expect(AsJobPreparedForDispatchTest::$prepared)->toBeFalse();
    AsJobPreparedForDispatchTest::assertPushed(1);
});
