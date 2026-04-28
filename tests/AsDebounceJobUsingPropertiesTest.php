<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\DebounceJobDecorator;

#[DebounceFor(30)]
class AsDebounceJobUsingPropertiesTest
{
    use AsJob;

    public string $jobDebounceId = 'my_debounce_id';

    public function handle(): void
    {
        //
    }
}

beforeEach(function () {
    Queue::fake();
});

it('makes debounce jobs by default when using properties', function () {
    // When we make a job from the action.
    $job = AsDebounceJobUsingPropertiesTest::makeJob();

    // Then it returns a DebounceJobDecorator.
    expect($job)->toBeInstanceOf(DebounceJobDecorator::class);
});

it('reads the debounce id from the action property', function () {
    // When we make a job from the action.
    $job = AsDebounceJobUsingPropertiesTest::makeJob();

    // Then the debounceId reflects the action's jobDebounceId property.
    expect($job->debounceId())->toBe('my_debounce_id');
});
