<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

class AsListenerWithShouldQueueTest implements ShouldQueue
{
    use AsListener;

    public static int $constructed = 0;
    public static int $handled = 0;
    public static int $shouldQueue = 0;
    public static ?int $latestResult;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle(string $operation, int $left, int $right): int
    {
        static::$handled++;

        return static::$latestResult = $operation === 'addition'
            ? $left + $right
            : $left - $right;
    }

    public function asListener(OperationRequestedEvent $event): void
    {
        $this->handle($event->operation, $event->left, $event->right);
    }

    public function shouldQueue(OperationRequestedEvent $event): bool
    {
        static::$shouldQueue++;

        return true;
    }
}

beforeEach(function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsListenerWithShouldQueueTest::class);

    // And reset the static properties between each test.
    AsListenerWithShouldQueueTest::$constructed = 0;
    AsListenerWithShouldQueueTest::$handled = 0;
    AsListenerWithShouldQueueTest::$shouldQueue = 0;
    AsListenerWithShouldQueueTest::$latestResult = null;
});

it('wraps the action in a CallQueuedListener', function () {
    // Given we mock the queue.
    Queue::fake();

    // When we dispatch an OperationRequestedEvent.
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));

    // Then the action was pushed in the queue via a CallQueuedListener job.
    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
        return $job->class === AsListenerWithShouldQueueTest::class
            && $job->method === 'handle' // No worries here, it'll be wrapped in a ListenerDecorator.
            && $job->data[0] instanceof OperationRequestedEvent;
    });
});

it('can run as a queued listener', function () {
    // When we dispatch an OperationRequestedEvent.
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));

    // Then the action was triggered as a queued listener.
    expect(AsListenerWithShouldQueueTest::$latestResult)->toBe(3);
    expect(AsListenerWithShouldQueueTest::$handled)->toBe(1);
    expect(AsListenerWithShouldQueueTest::$shouldQueue)->toBe(1);

    // And was constructed twice. Once before and once during the queued job.
    expect(AsListenerWithShouldQueueTest::$constructed)->toBe(2);
});
