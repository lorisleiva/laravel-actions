<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Lorisleiva\Actions\Concerns\AsCommand;

class AsCommandInSchedulerTest
{
    use AsCommand;

    public string $commandSignature = 'my:command';

    public function handle(): void
    {
        // ...
    }
}

beforeEach(function () {
    // Given we registered the action as a command.
    registerCommands([AsCommandInSchedulerTest::class]);
});

it('can be registered as a scheduled command', function () {
    /** @var Schedule $scheduler */
    $scheduler = app(Schedule::class);

    // Given we register the command in the scheduler.
    $scheduler->command(AsCommandInSchedulerTest::class)->everyMinute();

    // When we look for scheduler events matching our command.
    $matchingEvents = collect($scheduler->events())
        ->filter(function (Event $event) {
            return stripos($event->command, 'my:command');
        });

    // Then we found it.
    expect($matchingEvents)->not()->toBeEmpty();
});
