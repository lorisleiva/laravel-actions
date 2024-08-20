<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Support\Fluent;

trait AsObject
{
    public static function make()
    {
        return app(static::class);
    }

    /**
     * @see static::handle()
     */
    public static function run(mixed ...$arguments): mixed
    {
        return static::make()->handle(...$arguments);
    }

    public static function runIf(bool $boolean, mixed ...$arguments): mixed
    {
        return $boolean ? static::run(...$arguments) : new Fluent;
    }

    public static function runUnless(bool $boolean, mixed ...$arguments): mixed
    {
        return static::runIf(! $boolean, ...$arguments);
    }
}
