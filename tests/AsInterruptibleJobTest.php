<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\Interruptible;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\InterruptibleDebounceJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleUniqueJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleUniqueUntilProcessingJobDecorator;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Tests\Stubs\CustomInterruptibleJobDecorator;
use Lorisleiva\Actions\Tests\Stubs\InterruptibleAction;
use Lorisleiva\Actions\Tests\Stubs\InterruptibleDebounceAction;
use Lorisleiva\Actions\Tests\Stubs\InterruptibleUniqueAction;
use Lorisleiva\Actions\Tests\Stubs\InterruptibleUniqueUntilProcessingAction;

class AsInterruptibleJobTest
{
    use AsJob;

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    // Interruptible jobs are only available on Laravel 13.
    if (! interface_exists(Interruptible::class)) {
        test()->markTestSkipped('Interruptible jobs require Laravel 13.');
    }

    // Given we reset the static variables.
    InterruptibleAction::$interruptedWith = null;
});

it('makes interruptible jobs when the action implements Interruptible', function (string $action, string $decorator) {
    // When we make a job from an interruptible action.
    $job = $action::makeJob();

    // Then Laravel will notify it of worker signals.
    expect($job)->toBeInstanceOf($decorator);
    expect($job)->toBeInstanceOf(Interruptible::class);
})->with([
    'plain' => [InterruptibleAction::class, InterruptibleJobDecorator::class],
    'unique' => [InterruptibleUniqueAction::class, InterruptibleUniqueJobDecorator::class],
    'unique until processing' => [InterruptibleUniqueUntilProcessingAction::class, InterruptibleUniqueUntilProcessingJobDecorator::class],
    'debounced' => [InterruptibleDebounceAction::class, InterruptibleDebounceJobDecorator::class],
]);

it('does not make interruptible jobs for other actions', function () {
    // When we make a job from an action that doesn't implement Interruptible.
    $job = AsInterruptibleJobTest::makeJob();

    // Then Laravel won't notify it of worker signals.
    expect($job)->not->toBeInstanceOf(Interruptible::class);
});

it('uses a custom interruptible job decorator when one is registered', function () {
    // Given we registered a custom interruptible decorator.
    ActionManager::useInterruptibleJobDecorator(CustomInterruptibleJobDecorator::class);

    // When we make a job from an interruptible action.
    $job = InterruptibleAction::makeJob();

    // Then it uses the custom decorator.
    expect($job)->toBeInstanceOf(CustomInterruptibleJobDecorator::class);
});

it('passes the signal and the dispatched parameters to the action', function () {
    // Given a job made from an interruptible action.
    $job = InterruptibleAction::makeJob(42);

    // When the worker notifies it of a signal.
    $job->interrupted(SIGTERM);

    // Then the action received the signal and its parameters.
    expect(InterruptibleAction::$interruptedWith)->toBe([SIGTERM, 42]);
});

it('can be tested using the assertPushed helper method', function () {
    // Given we mock the queue driver.
    Queue::fake();

    // When we dispatch an interruptible action.
    InterruptibleAction::dispatch(10);

    // Then we can assert it has been pushed using helper methods.
    InterruptibleAction::assertPushed(fn ($action, array $parameters, JobDecorator $job) => $parameters === [10]);
});
