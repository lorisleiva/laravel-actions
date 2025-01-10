<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsPipeline;

class AsPipelineExplicitTest
{
    use AsPipeline;

    public function handle($passable): void
    {
        $passable->increment();
    }
}

class AsPipelineImplicitTest
{
    use AsAction;

    public function handle($passable): void
    {
        $passable->increment();
    }
}

function getAnonymous() {
    return function ($p, $next) {
        $p->increment();

        return $next($p);
    };
}

function getPassable() {
    return new class {
        public function __construct(public int $count = 0)
        {
            //
        }

        public function increment()
        {
            $this->count++;
        }
    };
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
