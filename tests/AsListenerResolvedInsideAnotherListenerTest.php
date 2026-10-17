<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class AsListenerResolvedInsideAnotherListenerTest
{
    use AsAction;

    public static array $calls = [];

    public function handle(int $userId): string
    {
        return static::$calls[] = "handle($userId)";
    }

    public function asListener(AsListenerResolvedInsideAnotherListenerTestOwnEvent $event): void
    {
        static::$calls[] = 'asListener';
    }
}

class AsListenerResolvedInsideAnotherListenerTestOwnEvent
{
}

class AsListenerResolvedInsideAnotherListenerTestOtherEvent
{
    public function __construct(public int $userId)
    {
    }
}

class AsListenerResolvedInsideAnotherListenerTestObserver
{
    public function handle(AsListenerResolvedInsideAnotherListenerTestOtherEvent $event): void
    {
        AsListenerResolvedInsideAnotherListenerTest::make()->handle($event->userId);
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsListenerResolvedInsideAnotherListenerTest::$calls = [];
});

it('runs handle when made inside another class listener', function () {
    // Given another class listens to an unrelated event and uses the action.
    Event::listen(
        AsListenerResolvedInsideAnotherListenerTestOtherEvent::class,
        AsListenerResolvedInsideAnotherListenerTestObserver::class
    );

    // When we dispatch that event.
    Event::dispatch(new AsListenerResolvedInsideAnotherListenerTestOtherEvent(42));

    // Then the action ran as an object.
    expect(AsListenerResolvedInsideAnotherListenerTest::$calls)->toBe(['handle(42)']);
});

it('runs handle when made inside a closure listener', function () {
    // Given a closure listens to an unrelated event and uses the action.
    Event::listen(function (AsListenerResolvedInsideAnotherListenerTestOtherEvent $event) {
        AsListenerResolvedInsideAnotherListenerTest::make()->handle($event->userId);
    });

    // When we dispatch that event.
    Event::dispatch(new AsListenerResolvedInsideAnotherListenerTestOtherEvent(42));

    // Then the action ran as an object.
    expect(AsListenerResolvedInsideAnotherListenerTest::$calls)->toBe(['handle(42)']);
});

it('runs handle when resolved from the container inside a closure listener', function () {
    // Given a closure listens to an unrelated event and resolves the action from the container.
    Event::listen(function (AsListenerResolvedInsideAnotherListenerTestOtherEvent $event) {
        app(AsListenerResolvedInsideAnotherListenerTest::class)->handle($event->userId);
    });

    // When we dispatch that event.
    Event::dispatch(new AsListenerResolvedInsideAnotherListenerTestOtherEvent(42));

    // Then the action ran as an object.
    expect(AsListenerResolvedInsideAnotherListenerTest::$calls)->toBe(['handle(42)']);
});

it('still runs asListener when the action is the listener', function () {
    // Given the action listens to its own event.
    Event::listen(
        AsListenerResolvedInsideAnotherListenerTestOwnEvent::class,
        AsListenerResolvedInsideAnotherListenerTest::class
    );

    // When we dispatch that event.
    Event::dispatch(new AsListenerResolvedInsideAnotherListenerTestOwnEvent());

    // Then the action ran as a listener.
    expect(AsListenerResolvedInsideAnotherListenerTest::$calls)->toBe(['asListener']);
});
