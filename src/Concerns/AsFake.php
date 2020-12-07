<?php

namespace Lorisleiva\Actions\Concerns;

use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;

trait AsFake
{
    protected static ?MockInterface $resolvedFakeInstance;

    public static function mock(): MockInterface
    {
        if (static::isFake()) {
            return static::$resolvedFakeInstance;
        }

        $mock = Mockery::mock(static::class);
        $mock->shouldAllowMockingProtectedMethods();
        app()->instance(static::class, $mock);

        return static::$resolvedFakeInstance = $mock;
    }

    public static function spy(): MockInterface
    {
        if (static::isFake()) {
            return static::$resolvedFakeInstance;
        }

        $spy = Mockery::spy(static::class);
        app()->instance(static::class, $spy);

        return static::$resolvedFakeInstance = $spy;
    }

    public static function partialMock(): MockInterface
    {
        return static::mock()->makePartial();
    }

    public static function shouldRun(): Expectation
    {
        return static::shouldReceive('handle');
    }

    public static function shouldNotRun(): Expectation
    {
        return static::shouldNotReceive('handle');
    }

    public static function shouldReceive(): Expectation
    {
        return static::mock()->shouldReceive(...func_get_args());
    }

    public static function shouldNotReceive(): Expectation
    {
        return static::mock()->shouldNotReceive(...func_get_args());
    }

    public static function isFake(): bool
    {
        return ! is_null(static::$resolvedFakeInstance);
    }
}
