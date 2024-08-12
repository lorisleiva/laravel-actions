<?php

namespace Lorisleiva\Actions\Concerns;

use Mockery;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Mockery\MockInterface;

trait AsFake
{
    public static function mock(): MockInterface
    {
        if (static::isFake()) {
            return static::getFakeResolvedInstance();
        }

        $mock = Mockery::mock(static::class);
        $mock->shouldAllowMockingProtectedMethods();

        return static::setFakeResolvedInstance($mock);
    }

    public static function spy(): MockInterface
    {
        if (static::isFake()) {
            return static::getFakeResolvedInstance();
        }

        return static::setFakeResolvedInstance(Mockery::spy(static::class));
    }

    public static function partialMock(): MockInterface
    {
        return static::mock()->makePartial();
    }

    public static function shouldRun(): Expectation|ExpectationInterface|HigherOrderMessage
    {
        return static::mock()->shouldReceive('handle');
    }

    public static function shouldNotRun(): Expectation|ExpectationInterface|HigherOrderMessage
    {
        return static::mock()->shouldNotReceive('handle');
    }

    public static function allowToRun(): Expectation|ExpectationInterface|HigherOrderMessage|MockInterface
    {
        return static::spy()->allows('handle');
    }

    public static function isFake(): bool
    {
        return app()->isShared(static::getFakeResolvedInstanceKey());
    }

    /**
     * Removes the fake instance from the container.
     */
    public static function clearFake(): void
    {
        app()->forgetInstance(static::getFakeResolvedInstanceKey());
    }

    protected static function setFakeResolvedInstance(MockInterface $fake): MockInterface
    {
        return app()->instance(static::getFakeResolvedInstanceKey(), $fake);
    }

    protected static function getFakeResolvedInstance(): ?MockInterface
    {
        return app(static::getFakeResolvedInstanceKey());
    }

    protected static function getFakeResolvedInstanceKey(): string
    {
        return 'LaravelActions:AsFake:' . static::class;
    }
}
