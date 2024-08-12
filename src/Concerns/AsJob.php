<?php

namespace Lorisleiva\Actions\Concerns;

use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Fluent;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\ActionPendingChain;
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
 * @method void configureJob(JobDecorator|UniqueJobDecorator $job)
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
 */
trait AsJob
{
    public static function makeJob(mixed ...$arguments): JobDecorator
    {
        if (static::jobShouldBeUnique()) {
            return static::makeUniqueJob(...$arguments);
        }

        return new ActionManager::$jobDecorator(static::class, ...$arguments);
    }

    public static function makeUniqueJob(mixed ...$arguments): UniqueJobDecorator
    {
        return new ActionManager::$uniqueJobDecorator(static::class, ...$arguments);
    }

    protected static function jobShouldBeUnique(): bool
    {
        return is_subclass_of(static::class, ShouldBeUnique::class);
    }

    public static function dispatch(mixed ...$arguments): PendingDispatch
    {
        return new PendingDispatch(static::makeJob(...$arguments));
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

        $decoratorClass = static::jobShouldBeUnique()
            ? ActionManager::$uniqueJobDecorator
            : ActionManager::$jobDecorator;

        $count = Queue::pushed($decoratorClass, function (JobDecorator $job, $queue) use ($callback) {
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
}
