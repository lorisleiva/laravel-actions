<?php

namespace Lorisleiva\Actions\Concerns;

use Mockery;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Mockery\MockInterface;

trait AsFake
{
    /** @var mixed|null */
    protected static $originalResolvedInstance = null;

    /** @var MockInterface|null  */
    protected static ?MockInterface $fakeResolvedInstance = null;

    /**
     * @return MockInterface
     */
    public static function mock(): MockInterface
    {
        if (static::isFake()) {
            return static::$fakeResolvedInstance;
        }

        $mock = Mockery::mock(static::class);
        $mock->shouldAllowMockingProtectedMethods();

        return static::setFakeResolvedInstance($mock);
    }

    /**
     * @return MockInterface
     */
    public static function spy(): MockInterface
    {
        if (static::isFake()) {
            return static::$fakeResolvedInstance;
        }

        return static::setFakeResolvedInstance(Mockery::spy(static::class));
    }

    /**
     * @return MockInterface
     */
    public static function partialMock(): MockInterface
    {
        return static::mock()->makePartial();
    }

    /**
     * @return Expectation|ExpectationInterface|HigherOrderMessage
     */
    public static function shouldRun()
    {
        return static::mock()->shouldReceive('handle');
    }

    /**
     * @return Expectation|ExpectationInterface|HigherOrderMessage
     */
    public static function shouldNotRun()
    {
        return static::mock()->shouldNotReceive('handle');
    }

    /**
     * @return Expectation|ExpectationInterface|HigherOrderMessage|MockInterface
     */
    public static function allowToRun()
    {
        return static::spy()->allows('handle');
    }

    /**
     * @return bool
     */
    public static function isFake(): bool
    {
        return ! is_null(static::$fakeResolvedInstance);
    }

    /**
     * Removes the fake instance from the action.
     */
    public static function clearFake(): void
    {
        static::$fakeResolvedInstance = null;

        if (is_null(static::$originalResolvedInstance)) {
            app()->forgetInstance(static::class);
        } else {
            app()->instance(static::class, static::$originalResolvedInstance);
            static::$originalResolvedInstance = null;
        }
    }

    /**
     * @param MockInterface $fake
     * @return MockInterface
     */
    protected static function setFakeResolvedInstance(MockInterface $fake): MockInterface
    {
        if (app()->isShared(static::class)) {
            static::$originalResolvedInstance = app(static::class);
        }

        app()->instance(static::class, $fake);

        return static::$fakeResolvedInstance = $fake;
    }
}
