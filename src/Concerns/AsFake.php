<?php

namespace Lorisleiva\Actions\Concerns;

use Mockery;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Mockery\MockInterface;

trait AsFake
{
    /**
     * @return MockInterface
     */
    public static function mock(): MockInterface
    {
        if (static::isFake()) {
            return static::getFakeResolvedInstance();
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
            return static::getFakeResolvedInstance();
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
        return app()->isShared(static::getFakeResolvedInstanceKey());
    }

    /**
     * Removes the fake instance from the container.
     */
    public static function clearFake(): void
    {
        app()->forgetInstance(static::getFakeResolvedInstanceKey());
    }

    /**
     * @param MockInterface $fake
     * @return MockInterface
     */
    protected static function setFakeResolvedInstance(MockInterface $fake): MockInterface
    {
        return app()->instance(static::getFakeResolvedInstanceKey(), $fake);
    }

    /**
     * @return MockInterface|null
     */
    protected static function getFakeResolvedInstance(): ?MockInterface
    {
        return app(static::getFakeResolvedInstanceKey());
    }

    /**
     * @return string
     */
    protected static function getFakeResolvedInstanceKey(): string
    {
        return 'LaravelActions:AsFake:' . static::class;
    }
}
