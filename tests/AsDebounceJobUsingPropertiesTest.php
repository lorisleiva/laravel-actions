<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\DebounceJobDecorator;
use LogicException;

class AsDebounceJobUsingPropertiesTest
{
    use AsJob;

    public int $jobDebounceFor = 20;

    public string $jobDebounceId = 'my_debounce_id';

    public function handle(): void
    {
        //
    }
}

class AsDebounceJobUsingPropertiesThatIsAlsoUniqueTest implements ShouldBeUnique
{
    use AsJob;

    public int $jobDebounceFor = 20;

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    // Debounced jobs are only available on Laravel 13.6+.
    if (! class_exists(DebounceFor::class)) {
        test()->markTestSkipped('Debounced jobs require Laravel 13.6+.');
    }

    Queue::fake();
});

it('makes debounce jobs from the jobDebounceFor property without the DebounceFor attribute', function () {
    // When we make a job from an action that only defines the jobDebounceFor property.
    $job = AsDebounceJobUsingPropertiesTest::makeJob();

    // Then it returns a DebounceJobDecorator.
    expect($job)->toBeInstanceOf(DebounceJobDecorator::class);
});

it('reads the debounce id from the action property', function () {
    // When we make a debounce job from the action.
    $job = AsDebounceJobUsingPropertiesTest::makeDebounceJob();

    // Then the debounceId reflects the action's jobDebounceId property.
    expect($job->debounceId())->toBe('my_debounce_id');
});

it('reads the debounceFor value from the action property', function () {
    // When we make a debounce job from the action.
    $job = AsDebounceJobUsingPropertiesTest::makeDebounceJob();

    // Then the debounceFor property is set from jobDebounceFor, with no maxWait.
    expect($job->debounceFor)->toBe(20);
    expect($job->maxWait)->toBeNull();
});

it('delays the job by the jobDebounceFor property', function () {
    // When we dispatch the action.
    AsDebounceJobUsingPropertiesTest::dispatch();

    // Then it was pushed with a debounce owner and delayed by jobDebounceFor.
    Queue::assertPushed(DebounceJobDecorator::class, function (DebounceJobDecorator $job) {
        return $job->debounceOwner !== '' && $job->delay === 20;
    });
});

it('cannot be both unique and debounced using properties', function () {
    // When we try to dispatch an action that is unique and has the jobDebounceFor property
    // Then a LogicException is thrown, matching Laravel's own behaviour.
    expect(fn () => AsDebounceJobUsingPropertiesThatIsAlsoUniqueTest::dispatch())
        ->toThrow(LogicException::class, 'A debounced job cannot also implement ShouldBeUnique.');
});
