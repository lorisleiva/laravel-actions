<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

class AsListenerAndObjectTest
{
    use AsObject;
    use AsListener;

    public static ?int $latestResult;

    public function handle(int $left, int $right, bool $addition = true): int
    {
        return $addition ? $left + $right : $left - $right;
    }

    public function asListener(OperationRequestedEvent $event): void
    {
        static::$latestResult = $this->handle(
            $event->left,
            $event->right,
            $event->operation === 'addition'
        );
    }
}

beforeEach(function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsListenerAndObjectTest::class);

    // And reset the latest result between tests.
    AsListenerAndObjectTest::$latestResult = null;
});

it('works as an object', function () {
    // When we run the action as an object.
    $result = AsListenerAndObjectTest::run(5, 3, false);

    // Then we get the expected result.
    expect($result)->toBe(2);

    // And the `asListener` method was not called.
    expect(AsListenerAndObjectTest::$latestResult)->toBeNull();
});

it('works as a listener', function () {
    // When we dispatch an OperationRequestedEvent.
    Event::dispatch(new OperationRequestedEvent('substraction', 5, 3));

    // Then we get the expected result from the `asListener` method.
    expect(AsListenerAndObjectTest::$latestResult)->toBe(2);
});
