<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Bus\PendingChain;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Fluent;
use Lorisleiva\Actions\Decorators\JobDecorator;

trait AsJob
{
    public static function dispatch(...$arguments)
    {
        return new PendingDispatch(
            new JobDecorator(static::class, ...$arguments)
        );
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
        return app(Dispatcher::class)->dispatchSync(
            new JobDecorator(static::class, ...$arguments)
        );
    }

    public static function dispatchNow(...$arguments)
    {
        return static::dispatchSync(...$arguments);
    }

    public static function dispatchAfterResponse(...$arguments)
    {
        return app(Dispatcher::class)->dispatchAfterResponse(
            new JobDecorator(static::class, ...$arguments)
        );
    }

    public static function withChain($chain, ...$arguments)
    {
        return new PendingChain(
            new JobDecorator(static::class, ...$arguments),
            $chain
        );
    }
}
