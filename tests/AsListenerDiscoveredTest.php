<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\EventServiceProvider;
use Lorisleiva\Actions\Tests\Stubs\AsListenerAction;
use Lorisleiva\Actions\Tests\Stubs\AsListenerHandleAction;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

uses(DiscoversListeners::class);

dataset('ListenerActions', [
    'Action@asListener' => [AsListenerAction::class],
    'Action@handle' => [AsListenerHandleAction::class],
]);

beforeEach(function () {
    // And reset the static properties between each test.
    AsListenerAction::$constructed = 0;
    AsListenerAction::$handled = 0;
    AsListenerAction::$latestResult = null;
    AsListenerHandleAction::$constructed = 0;
    AsListenerHandleAction::$handled = 0;
    AsListenerHandleAction::$latestResult = null;
});

it('can run as an auto-discovered event listener', function ($class) {
    // When we dispatch an OperationRequestedEvent.
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));

    // Then the action was triggered as a listener.
    expect($class::$latestResult)->toBe(3);
})->with('ListenerActions');

it('is constructed and handled everytime it is triggered as a discovered listener', function ($class) {
    // When we dispatch two OperationRequestedEvents.
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));
    Event::dispatch(new OperationRequestedEvent('addition', 1, 2));

    // Then the action was constructed and handled twice.
    expect($class::$constructed)->toBe(2);
    expect($class::$handled)->toBe(2);
})->with('ListenerActions');
