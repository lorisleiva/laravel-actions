<?php

namespace Lorisleiva\Actions\Concerns;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Interruptible;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Fluent;
use Illuminate\Support\Testing\Fakes\QueueFake;
use LogicException;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\ActionPendingChain;
use Lorisleiva\Actions\DebouncePendingDispatch;
use Lorisleiva\Actions\Decorators\DebounceJobDecorator;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use PHPUnit\Framework\Assert as PHPUnit;
use Throwable;

/**
 * @property-read string $jobConnection
 * @property-read string $jobQueue
 * @property-read int $jobTries
 * @property-read int $jobMaxExceptions
 * @property-read int $jobTimeout
 * @method void configureJob(JobDecorator $job)
 *
 * @property-read int|array $jobBackoff
 * @method int|array getJobBackoff()
 *
 * @property-read \DateTime|int $jobRetryUntil
 * @method \DateTime|int getJobRetryUntil()
 *
 * @method array getJobMiddleware()
 *
 * @method void jobFailed(Throwable $e)
 *
 * @method string getJobDisplayName()
 *
 * @method array getJobTags()
 *
 * @property-read int $jobUniqueFor
 * @method int getJobUniqueFor()
 *
 * @property-read int $jobUniqueId
 * @method int getJobUniqueId()
 *
 * @method int getJobUniqueVia()
 *
 * @property-read bool $jobDeleteWhenMissingModels
 * @method bool getJobDeleteWhenMissingModels()
 *
 * @property-read int $jobDebounceFor
 * @method int getJobDebounceFor()
 *
 * @property-read string $jobDebounceId
 * @method string getJobDebounceId()
 *
 * @method \Illuminate\Contracts\Cache\Repository getJobDebounceVia()
 *
 */
trait AsJob
{
    public static function makeJob(mixed ...$arguments): JobDecorator
    {
        $unique = static::jobShouldBeUnique();
        $debounced = static::jobShouldBeDebounced();

        if ($unique && $debounced) {
            throw new LogicException('A debounced job cannot also implement ShouldBeUnique.');
        }

        if ($unique) {
            return static::makeUniqueJob(...$arguments);
        }

        if ($debounced) {
            return static::makeDebounceJob(...$arguments);
        }

        $decorator = static::defaultJobDecoratorClass();

        return new $decorator(static::class, ...$arguments);
    }

    public static function makeUniqueJob(mixed ...$arguments): UniqueJobDecorator
    {
        if (static::jobShouldBeUniqueUntilProcessing()) {
            return static::makeUniqueUntilProcessingJob(...$arguments);
        }

        $decorator = static::uniqueJobDecoratorClass();

        return new $decorator(static::class, ...$arguments);
    }

    /**
     * @return UniqueJobDecorator&ShouldBeUniqueUntilProcessing
     */
    public static function makeUniqueUntilProcessingJob(mixed ...$arguments): UniqueJobDecorator
    {
        $decorator = static::uniqueUntilProcessingJobDecoratorClass();

        return new $decorator(static::class, ...$arguments);
    }

    public static function makeDebounceJob(mixed ...$arguments): DebounceJobDecorator
    {
        $decorator = static::debounceJobDecoratorClass();

        return new $decorator(static::class, ...$arguments);
    }

    /**
     * The decorator class makeJob() wraps this action in.
     *
     * @return class-string<JobDecorator>
     */
    protected static function jobDecoratorClass(): string
    {
        return match (true) {
            static::jobShouldBeUniqueUntilProcessing() => static::uniqueUntilProcessingJobDecoratorClass(),
            static::jobShouldBeUnique() => static::uniqueJobDecoratorClass(),
            static::jobShouldBeDebounced() => static::debounceJobDecoratorClass(),
            default => static::defaultJobDecoratorClass(),
        };
    }

    /**
     * @return class-string<JobDecorator>
     */
    protected static function defaultJobDecoratorClass(): string
    {
        return static::jobDecoratorFor(
            ActionManager::$jobDecorator,
            ActionManager::$interruptibleJobDecorator,
            'useInterruptibleJobDecorator'
        );
    }

    /**
     * @return class-string<UniqueJobDecorator>
     */
    protected static function uniqueJobDecoratorClass(): string
    {
        return static::jobDecoratorFor(
            ActionManager::$uniqueJobDecorator,
            ActionManager::$interruptibleUniqueJobDecorator,
            'useInterruptibleUniqueJobDecorator'
        );
    }

    /**
     * @return class-string<UniqueJobDecorator&ShouldBeUniqueUntilProcessing>
     */
    protected static function uniqueUntilProcessingJobDecoratorClass(): string
    {
        static::ensureDecoratorVariantExtends(
            ActionManager::$uniqueUntilProcessingJobDecorator,
            ActionManager::$uniqueJobDecorator,
            ShouldBeUniqueUntilProcessing::class,
            'useUniqueUntilProcessingJobDecorator'
        );

        return static::jobDecoratorFor(
            ActionManager::$uniqueUntilProcessingJobDecorator,
            ActionManager::$interruptibleUniqueUntilProcessingJobDecorator,
            'useInterruptibleUniqueUntilProcessingJobDecorator'
        );
    }

    /**
     * @return class-string<DebounceJobDecorator>
     */
    protected static function debounceJobDecoratorClass(): string
    {
        return static::jobDecoratorFor(
            ActionManager::$debounceJobDecorator,
            ActionManager::$interruptibleDebounceJobDecorator,
            'useInterruptibleDebounceJobDecorator'
        );
    }

    /**
     * Laravel only notifies a running job of worker signals when the queued
     * command implements Interruptible, so interruptible actions get a
     * decorator that implements it and other actions don't.
     *
     * @template TDecorator of JobDecorator
     * @template TInterruptibleDecorator of TDecorator
     * @param class-string<TDecorator> $decorator
     * @param class-string<TInterruptibleDecorator> $interruptibleDecorator
     * @return class-string<TDecorator>
     */
    protected static function jobDecoratorFor(string $decorator, string $interruptibleDecorator, string $interruptibleSetter): string
    {
        if (! static::jobShouldBeInterruptible()) {
            return $decorator;
        }

        static::ensureDecoratorVariantExtends($interruptibleDecorator, $decorator, Interruptible::class, $interruptibleSetter);

        return $interruptibleDecorator;
    }

    /**
     * A variant decorator replaces the registered one, so it must extend it.
     * Otherwise a custom decorator's behaviour would be silently dropped for
     * actions that implement the variant's interface.
     *
     * @param class-string<JobDecorator> $variant
     * @param class-string<JobDecorator> $decorator
     */
    protected static function ensureDecoratorVariantExtends(string $variant, string $decorator, string $interface, string $setter): void
    {
        if (is_a($variant, $decorator, true)) {
            return;
        }

        throw new LogicException(sprintf(
            '[%s] implements %s, so it is queued with [%s] instead of the registered [%s], which it does not extend. Register a decorator that extends [%s] with ActionManager::%s().',
            static::class,
            class_basename($interface),
            $variant,
            $decorator,
            $decorator,
            $setter
        ));
    }

    protected static function jobShouldBeUnique(): bool
    {
        return is_subclass_of(static::class, ShouldBeUnique::class);
    }

    protected static function jobShouldBeUniqueUntilProcessing(): bool
    {
        return is_subclass_of(static::class, ShouldBeUniqueUntilProcessing::class);
    }

    protected static function jobShouldBeInterruptible(): bool
    {
        return is_subclass_of(static::class, Interruptible::class);
    }

    protected static function jobShouldBeDebounced(): bool
    {
        return DebounceJobDecorator::shouldDebounce(static::class);
    }

    public static function dispatch(mixed ...$arguments): PendingDispatch
    {
        $job = static::makeJob(...$arguments);

        return $job instanceof DebounceJobDecorator
            ? new DebouncePendingDispatch($job)
            : new PendingDispatch($job);
    }

    public static function dispatchIf(bool $boolean, mixed ...$arguments): PendingDispatch|Fluent
    {
        return $boolean ? static::dispatch(...$arguments) : new Fluent;
    }

    public static function dispatchUnless(bool $boolean, mixed ...$arguments): PendingDispatch|Fluent
    {
        return static::dispatchIf(! $boolean, ...$arguments);
    }

    public static function dispatchSync(mixed ...$arguments): mixed
    {
        return app(Dispatcher::class)->dispatchSync(static::makeJob(...$arguments));
    }

    public static function dispatchNow(mixed ...$arguments): mixed
    {
        return static::dispatchSync(...$arguments);
    }

    public static function dispatchAfterResponse(mixed ...$arguments): void
    {
        static::dispatch(...$arguments)->afterResponse();
    }

    public static function withChain(array $chain): ActionPendingChain
    {
        return new ActionPendingChain(static::class, $chain);
    }

    public static function assertPushed(Closure|int|null $times = null, Closure|null $callback = null): void
    {
        if ($times instanceof Closure) {
            $callback = $times;
            $times = null;
        }

        $decoratorClass = static::jobDecoratorClass();

        // pushed() only exists once the queue has been faked.
        /** @var QueueFake $fake */
        $fake = Queue::getFacadeRoot();

        $count = $fake->pushed($decoratorClass, function (JobDecorator $job, $queue) use ($callback) {
            if (! $job->decorates(static::class)) {
                return false;
            }

            if (! $callback) {
                return true;
            }

            return $callback($job->getAction(), $job->getParameters(), $job, $queue);
        })->count();

        $job = static::class;

        if (is_null($times)) {
            PHPUnit::assertTrue(
                $count > 0,
                "The expected [{$job}] job was not pushed."
            );
        } elseif ($times === 0) {
            PHPUnit::assertTrue(
                $count === 0,
                "The unexpected [{$job}] job was pushed."
            );
        } else {
            PHPUnit::assertSame(
                $times,
                $count,
                "The expected [{$job}] job was pushed {$count} times instead of {$times} times."
            );
        }
    }

    public static function assertNotPushed(Closure|null $callback = null): void
    {
        static::assertPushed(0, $callback);
    }

    public static function assertPushedOn(string $queue, Closure|int|null $times = null, Closure|null $callback = null): void
    {
        if ($times instanceof Closure) {
            $callback = $times;
            $times = null;
        }

        static::assertPushed($times, function ($action, $parameters, $job, $pushedQueue) use ($callback, $queue) {
            if ($pushedQueue !== $queue) {
                return false;
            }

            return $callback ? $callback(...func_get_args()) : true;
        });
    }

    public static function assertPushedWith(Closure|array $callback, ?string $queue = null): void
    {
        if (is_array($callback)) {
            $callback = fn (...$params) => $params === $callback;
        }

        static::assertPushed(
            fn ($action, $params, JobDecorator $job, $q) => $callback(...$params) && (is_null($queue) || $q === $queue)
        );
    }

    public static function assertNotPushedWith(Closure|array $callback): void
    {
        if (is_array($callback)) {
            $callback = fn (...$params) => $params === $callback;
        }

        static::assertNotPushed(fn ($action, $params, JobDecorator $job) => $callback(...$job->getParameters()));
    }
}
