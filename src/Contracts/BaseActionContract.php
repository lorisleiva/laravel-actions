<?php

namespace Lorisleiva\Actions\Contracts;

use Closure;
use Illuminate\Foundation\Bus\PendingDispatch;
use Lorisleiva\Actions\ActionPendingChain;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Mockery\MockInterface;

interface BaseActionContract
{
    // AsObject
    public static function make();
    public static function run(...$arguments);
    public static function runIf($boolean, ...$arguments);
    public static function runUnless($boolean, ...$arguments);

    // AsJob
    public static function makeJob(...$arguments): JobDecorator;
    public static function makeUniqueJob(...$arguments): UniqueJobDecorator;
    public static function dispatch(...$arguments): PendingDispatch;
    public static function dispatchIf($boolean, ...$arguments);
    public static function dispatchUnless($boolean, ...$arguments);
    public static function dispatchSync(...$arguments);
    public static function dispatchNow(...$arguments);
    public static function dispatchAfterResponse(...$arguments): void;
    public static function withChain($chain): ActionPendingChain;
    public static function assertPushed($times = null, Closure $callback = null): void;
    public static function assertNotPushed(Closure $callback = null): void;
    public static function assertPushedOn(string $queue, $times = null, Closure $callback = null): void;

    // AsFake
    public static function mock(): MockInterface;
    public static function spy(): MockInterface;
    public static function partialMock(): MockInterface;
    public static function shouldRun();
    public static function shouldNotRun();
    public static function allowToRun();
    public static function isFake(): bool;
    public static function clearFake(): void;
}
