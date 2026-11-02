<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\UniqueUntilProcessingJobDecorator;
use Lorisleiva\Actions\Tests\Stubs\CustomUniqueJobDecorator;
use Lorisleiva\Actions\Tests\Stubs\CustomUniqueUntilProcessingJobDecorator;

class AsUniqueUntilProcessingJobTest implements ShouldBeUniqueUntilProcessing
{
    use AsJob;

    public static int $runs = 0;

    // Dispatching itself again while running only goes through if the unique lock is already free.
    public function handle(int $id = 1): void
    {
        if (++static::$runs === 1) {
            static::dispatch($id);
        }
    }

    public function getJobUniqueId(int $id = 1): int
    {
        return $id;
    }
}

class AsUniqueUntilProcessingJobComparisonTest implements ShouldBeUnique
{
    use AsJob;

    public static int $runs = 0;

    // Dispatching itself again while running only goes through if the unique lock is already free.
    public function handle(): void
    {
        if (++static::$runs === 1) {
            static::dispatch();
        }
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsUniqueUntilProcessingJobTest::$runs = 0;
    AsUniqueUntilProcessingJobComparisonTest::$runs = 0;
});

it('makes unique until processing jobs when the action implements ShouldBeUniqueUntilProcessing', function () {
    // When we make a job from the action.
    $job = AsUniqueUntilProcessingJobTest::makeJob();

    // Then it returns a decorator that Laravel treats as unique until processing.
    expect($job)->toBeInstanceOf(UniqueUntilProcessingJobDecorator::class);
    expect($job)->toBeInstanceOf(ShouldBeUniqueUntilProcessing::class);
});

it('dispatches multiple unique until processing jobs once', function () {
    // Given we mock the queue driver.
    Queue::fake();

    // When we dispatch two jobs with the same id.
    AsUniqueUntilProcessingJobTest::dispatch(42);
    AsUniqueUntilProcessingJobTest::dispatch(42);

    // Then we have dispatched it only once.
    Queue::assertPushed(UniqueUntilProcessingJobDecorator::class, 1);
});

it('releases the unique lock before the job is processed', function () {
    // When we dispatch the job on the sync queue, and it dispatches itself again while running.
    AsUniqueUntilProcessingJobTest::dispatch(42);

    // Then the lock was already released, so the second dispatch ran too.
    expect(AsUniqueUntilProcessingJobTest::$runs)->toBe(2);
});

it('keeps the unique lock while a plain unique job is processed', function () {
    // When we dispatch a plain unique job on the sync queue, and it dispatches itself again while running.
    AsUniqueUntilProcessingJobComparisonTest::dispatch();

    // Then the lock was still held, so the second dispatch was dropped.
    expect(AsUniqueUntilProcessingJobComparisonTest::$runs)->toBe(1);
});

it('can be tested using the assertPushed helper method', function () {
    // Given we mock the queue driver.
    Queue::fake();

    // When we dispatch the job.
    AsUniqueUntilProcessingJobTest::dispatch(10);

    // Then we can assert it has been pushed using helper methods.
    AsUniqueUntilProcessingJobTest::assertPushed();
});

it('refuses to drop a custom unique job decorator', function () {
    // Given we registered a custom unique decorator, but no unique until processing one that extends it.
    ActionManager::useUniqueJobDecorator(CustomUniqueJobDecorator::class);

    // When we make a job from a unique until processing action.
    // Then it fails loudly rather than silently skipping the custom decorator.
    expect(fn () => AsUniqueUntilProcessingJobTest::makeJob())
        ->toThrow(LogicException::class, 'ActionManager::useUniqueUntilProcessingJobDecorator()');
});

it('uses a custom unique until processing job decorator that extends the custom unique one', function () {
    // Given we registered a custom unique decorator and a unique until processing one that extends it.
    ActionManager::useUniqueJobDecorator(CustomUniqueJobDecorator::class);
    ActionManager::useUniqueUntilProcessingJobDecorator(CustomUniqueUntilProcessingJobDecorator::class);

    // When we make a job from a unique until processing action.
    $job = AsUniqueUntilProcessingJobTest::makeJob();

    // Then it keeps the custom behaviour and is unique until processing.
    expect($job)->toBeInstanceOf(CustomUniqueJobDecorator::class);
    expect($job)->toBeInstanceOf(ShouldBeUniqueUntilProcessing::class);
});
