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
    public static function makeJob(...$arguments)
    {
        if (static::jobShouldBeUnique()) {
            return static::makeUniqueJob(...$arguments);
        }

        return new JobDecorator(static::class, ...$arguments);
    }

    public static function makeUniqueJob(...$arguments)
    {
        return new UniqueJobDecorator(static::class, ...$arguments);
    }

    protected static function jobShouldBeUnique()
    {
        return method_exists(static::class, 'getJobUniqueId')
            || property_exists(static::class, 'jobUniqueId');
    }

    public static function dispatch(...$arguments)
    {
        return new PendingDispatch(static::makeJob(...$arguments));
    }

    public static function dispatchIf($boolean, ...$arguments)
    {
        return $boolean ? static::dispatch(...$arguments) : new Fluent;
    }

    public static function dispatchUnless($boolean, ...$arguments)
    {
        return static::dispatchIf(! $boolean, ...$arguments);
    }

    public static function dispatchSync(...$arguments)
    {
        return app(Dispatcher::class)->dispatchSync(static::makeJob(...$arguments));
    }

    public static function dispatchNow(...$arguments)
    {
        return static::dispatchSync(...$arguments);
    }

    public static function dispatchAfterResponse(...$arguments)
    {
        return app(Dispatcher::class)->dispatchAfterResponse(static::makeJob(...$arguments));
    }

    public static function withChain($chain)
    {
        return new ActionPendingChain(static::class, $chain);
    }
}
