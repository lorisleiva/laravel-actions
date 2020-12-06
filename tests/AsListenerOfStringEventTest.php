<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsListener;

class AsListenerOfStringEventTest
{
    use AsListener;

    public static array $latestParameters = [];

    public function handle(...$parameters): void
    {
        static::$latestParameters = $parameters;
    }
}

beforeEach(function () {
    // Given we reset the static properties between each test.
    AsListenerOfStringEventTest::$latestParameters = [];
});

it('can run as an event listener for string events', function () {
    // Given we are listening for a string event.
    Event::listen('my_string_event', AsListenerOfStringEventTest::class);

    // When we dispatch that string event with some parameters.
    Event::dispatch('my_string_event', $parameters = [1, 'two', new Filesystem()]);

    // Then the handle method was called with these parameters.
    expect(AsListenerOfStringEventTest::$latestParameters)->toBe($parameters);
});
