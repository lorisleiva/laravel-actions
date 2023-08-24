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
 * @method int|array getJobBackoff(...$parameters)
 *
 * @property-read \DateTime|int $jobRetryUntil
 * @method \DateTime|int getJobRetryUntil(...$parameters)
 *
 * @method array getJobMiddleware(...$parameters)
 *
 * @method void jobFailed(Throwable $e, ...$parameters)
 *
 * @method string getJobDisplayName(...$parameters)
 *
 * @method array getJobTags(...$parameters)
 *
 * @property-read int $jobUniqueFor
 * @method int getJobUniqueFor(...$parameters)
 *
 * @property-read int $jobUniqueId
 * @method int getJobUniqueId(...$parameters)
 *
 * @method int getJobUniqueVia(...$parameters)
 *
 */
trait AsJob
{
    /**
     * @param mixed ...$arguments
     * @return JobDecorator|UniqueJobDecorator
     */
    public static function makeJob(...$arguments): JobDecorator
    {
        if (static::jobShouldBeUnique()) {
            return static::makeUniqueJob(...$arguments);
        }

        return new ActionManager::$jobDecorator(static::class, ...$arguments);
    }

    /**
     * @param mixed ...$arguments
     * @return UniqueJobDecorator
     */
    public static function makeUniqueJob(...$arguments): UniqueJobDecorator
    {
        return new ActionManager::$uniqueJobDecorator(static::class, ...$arguments);
    }

    /**
     * @return bool
     */
    protected static function jobShouldBeUnique(): bool
    {
        return is_subclass_of(static::class, ShouldBeUnique::class);
    }

    /**
     * @param mixed ...$arguments
     * @return PendingDispatch
     */
    public static function dispatch(...$arguments): PendingDispatch
    {
        return new PendingDispatch(static::makeJob(...$arguments));
    }

    /**
     * @param $boolean
     * @param mixed ...$arguments
     * @return PendingDispatch|Fluent
     */
    public static function dispatchIf($boolean, ...$arguments)
    {
        return $boolean ? static::dispatch(...$arguments) : new Fluent;
    }

    /**
     * @param $boolean
     * @param mixed ...$arguments
     * @return PendingDispatch|Fluent
     */
    public static function dispatchUnless($boolean, ...$arguments)
    {
        return static::dispatchIf(! $boolean, ...$arguments);
    }

    /**
     * @param mixed ...$arguments
     * @return mixed
     */
    public static function dispatchSync(...$arguments)
    {
        return app(Dispatcher::class)->dispatchSync(static::makeJob(...$arguments));
    }

    /**
     * @param mixed ...$arguments
     * @return mixed
     */
    public static function dispatchNow(...$arguments)
    {
        return static::dispatchSync(...$arguments);
    }

    /**
     * @param mixed ...$arguments
     * @return void
     */
    public static function dispatchAfterResponse(...$arguments): void
    {
        static::dispatch(...$arguments)->afterResponse();
    }

    /**
     * @param $chain
     * @return ActionPendingChain
     */
    public static function withChain($chain): ActionPendingChain
    {
        return new ActionPendingChain(static::class, $chain);
    }

    /**
     * @param Closure|int|null $times
     * @param Closure|null $callback
     */
    public static function assertPushed($times = null, Closure $callback = null): void
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

    /**
     * @param Closure|null $callback
     */
    public static function assertNotPushed(Closure $callback = null): void
    {
        static::assertPushed(0, $callback);
    }

    /**
     * @param string $queue
     * @param Closure|int|null $times
     * @param Closure|null $callback
     */
    public static function assertPushedOn(string $queue, $times = null, Closure $callback = null): void
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
