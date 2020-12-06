<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Fluent;
use Lorisleiva\Actions\ActionPendingChain;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;

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

        return new JobDecorator(static::class, ...$arguments);
    }

    /**
     * @param mixed ...$arguments
     * @return UniqueJobDecorator
     */
    public static function makeUniqueJob(...$arguments): UniqueJobDecorator
    {
        return new UniqueJobDecorator(static::class, ...$arguments);
    }

    /**
     * @return bool
     */
    protected static function jobShouldBeUnique(): bool
    {
        return method_exists(static::class, 'getJobUniqueId')
            || property_exists(static::class, 'jobUniqueId');
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
        app(Dispatcher::class)->dispatchAfterResponse(static::makeJob(...$arguments));
    }

    /**
     * @param $chain
     * @return ActionPendingChain
     */
    public static function withChain($chain): ActionPendingChain
    {
        return new ActionPendingChain(static::class, $chain);
    }
}
