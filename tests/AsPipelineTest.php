<?php

namespace Lorisleiva\Actions\Tests;

use ArgumentCountError;
use Closure;
use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsPipeline;

class AsPipelinePassable
{
    public function __construct(public int $count = 0)
    {
        //
    }

    public function increment(int $multiplier = 1)
    {
        $this->count = $this->count + (1 * $multiplier);
    }
}

class AsPipelineExplicitTest
{
    use AsPipeline;

    public function handle(AsPipelinePassable $passable): void
    {
        $passable->increment();
    }

    public function asPipeline(AsPipelinePassable $passable): AsPipelinePassable
    {
        $this->handle($passable);

        return $passable;
    }
}

class AsPipelineImplicitTest
{
    use AsAction;

    public function handle(AsPipelinePassable $passable): void
    {
        $passable->increment();
    }

    public function asPipeline(AsPipelinePassable $passable): AsPipelinePassable
    {
        $this->handle($passable);

        return $passable;
    }
}

class AsPipelineMultipleParamTest
{
    use AsAction;

    public function handle(AsPipelinePassable $passable): void
    {
        $passable->increment($multiplier);
    }

    public function asPipeline(AsPipelinePassable $passable, int $multiplier): AsPipelinePassable
    {
        $this->handle($passable);

        return $passable;
    }
}

class AsPipelineSingleParamHandleOnlyTest
{
    use AsAction;

    public function handle(AsPipelinePassable $passable): void
    {
        $passable->increment();
    }
}

class AsPipelineMultipleParamHandleOnlyTest
{
    use AsAction;

    public function handle(AsPipelinePassable $passable, int $multiplier): void
    {
        $passable->increment($multiplier);
    }
}

class AsPipelineWithoutHandleOrAsPipeline
{
    use AsAction;
}

function getAnonymous() {
    return function (AsPipelinePassable $p, $next) {
        $p->increment();

        return $next($p);
    };
}

function getPassable() {
    return new AsPipelinePassable;
}

it('can run as a pipe in a pipeline, with explicit trait', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->through([
            AsPipelineExplicitTest::class,
            $anonymous,
            AsPipelineExplicitTest::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(4);
});

it('can run as a pipe in a pipeline, with implicit trait', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->through([
            AsPipelineImplicitTest::class,
            $anonymous,
            AsPipelineImplicitTest::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(4);
});

it('can run as a pipe in a pipeline, without an explicit asPipeline method', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->through([
            AsPipelineSingleParamHandleOnlyTest::class,
            $anonymous,
            AsPipelineSingleParamHandleOnlyTest::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(4);
});

it('it can run as a noop/passthrough pipe in a pipeline, without a handle or asPipeline method', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->through([
            AsPipelineWithoutHandleOrAsPipeline::class,
            $anonymous,
            AsPipelineWithoutHandleOrAsPipeline::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(2);
});

it('it can run with an arbitrary via method configured on Pipeline', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->via('foobar')
        ->through([
            AsPipelineImplicitTest::class,
            $anonymous,
            AsPipelineImplicitTest::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(4);
});

it('cannot run as a pipe in a pipeline, with an explicit asPipeline method expecting multiple non-optional params', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->through([
            AsPipelineMultipleParamTest::class,
            $anonymous,
            AsPipelineMultipleParamTest::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(10);
})->throws(ArgumentCountError::class, 'Too few arguments to function Lorisleiva\Actions\Tests\AsPipelineMultipleParamTest::asPipeline(), 1 passed and exactly 2 expected');

it('cannot run as a pipe in a pipeline, without an explicit asPipeline method and multiple non-optional handle params', function () {
    $anonymous = getAnonymous();
    $passable = Pipeline::send(getPassable())
        ->through([
            AsPipelineMultipleParamHandleOnlyTest::class,
            $anonymous,
            AsPipelineMultipleParamHandleOnlyTest::class,
            $anonymous,
        ])
        ->thenReturn();

    expect(is_object($passable))->toBe(true);
    expect($passable->count)->toBe(10);
})->throws(ArgumentCountError::class, 'Too few arguments to function Lorisleiva\Actions\Tests\AsPipelineMultipleParamHandleOnlyTest::handle(), 1 passed and exactly 2 expected');
