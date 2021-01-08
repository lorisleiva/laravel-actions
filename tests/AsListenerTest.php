<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

class AsListenerTest
{
    use AsListener;

    public static int $constructed = 0;
    public static int $handled = 0;
    public static ?int $latestResult;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle(OperationRequestedEvent $event): void
    {
        static::$handled++;
        static::$latestResult = $event->operation === 'addition'
            ? $event->left + $event->right
            : $event->left - $event->right;
    }
}

beforeEach(function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsListenerTest::class);

    // And reset the static properties between each test.
    AsListenerTest::$constructed = 0;
    AsListenerTest::$handled = 0;
    AsListenerTest::$latestResult = null;
});

it('can run as an event listener', function () {
    // When we dispatch an OperationRequestedEvent.
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));

    // Then the action was triggered as a listener.
    expect(AsListenerTest::$latestResult)->toBe(3);
});

it('is constructed and handled everytime it is triggered as a listener', function () {
    // When we dispatch two OperationRequestedEvents.
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));

    // Then the action was constructed and handled twice.
    expect(AsListenerTest::$constructed)->toBe(2);
    expect(AsListenerTest::$handled)->toBe(2);
});
