<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedAfterCommitEvent;

class AsListenerWithShouldDispatchAfterCommitTest
{
    use AsObject;
    use AsListener;

    public static ?int $latestResult;

    public function handle(int $left, int $right, bool $addition = true): int
    {
        return $addition ? $left + $right : $left - $right;
    }

    public function asListener(OperationRequestedAfterCommitEvent $event): void
    {
        static::$latestResult = $this->handle(
            $event->left,
            $event->right,
            $event->operation === 'addition'
        );
    }
}

beforeEach(function () {
    // Given we are listening for the OperationRequestedAfterCommitEvent.
    Event::listen(OperationRequestedAfterCommitEvent::class, AsListenerWithShouldDispatchAfterCommitTest::class);

    // And reset the latest result between tests.
    AsListenerWithShouldDispatchAfterCommitTest::$latestResult = null;
});

it('works as an object even when the event implements ShouldDispatchAfterCommit', function () {
    // When we run the action as an object.
    $result = AsListenerWithShouldDispatchAfterCommitTest::run(5, 3, false);

    // Then we get the expected result.
    expect($result)->toBe(2);

    // And the `asListener` method was not called.
    expect(AsListenerWithShouldDispatchAfterCommitTest::$latestResult)->toBeNull();
});

it('calls asListener() when event implements ShouldDispatchAfterCommit and is dispatched outside a transaction', function () {
    // When we dispatch the event outside of a transaction.
    Event::dispatch(new OperationRequestedAfterCommitEvent('addition', 1, 2));

    // Then asListener() was called via the ListenerDecorator.
    expect(AsListenerWithShouldDispatchAfterCommitTest::$latestResult)->toBe(3);
});

it('calls asListener() when event implements ShouldDispatchAfterCommit and is dispatched inside a transaction', function () {
    // When we dispatch the event inside a transaction and commit.
    DB::beginTransaction();
    Event::dispatch(new OperationRequestedAfterCommitEvent('addition', 1, 2));

    // Then asListener() has not been called yet (deferred until commit).
    expect(AsListenerWithShouldDispatchAfterCommitTest::$latestResult)->toBeNull();

    DB::commit();

    // Then asListener() was called via the ListenerDecorator after the commit.
    expect(AsListenerWithShouldDispatchAfterCommitTest::$latestResult)->toBe(3);
});
