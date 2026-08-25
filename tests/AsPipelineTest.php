<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Tests\Stubs\PipelinePassable;

class AsPipelineTest
{
    use AsAction;

    public function handle(PipelinePassable $passable): void
    {
        $passable->increment();
    }

    public function asPipeline(PipelinePassable $passable): void
    {
        $this->handle($passable);
    }
}

it('can run as a pipe in a pipeline, with an explicit asPipeline method', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            AsPipelineTest::class,
            AsPipelineTest::class,
            AsPipelineTest::class,
            AsPipelineTest::class,
        ])
        ->thenReturn();

    expect(is_a($passable, PipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(4);
});

it('can run with an arbitrary via method configured on Pipeline', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->via('arbitraryMethodThatDoesNotExistOnTheAction')
        ->through([
            AsPipelineTest::class,
            app()->make(AsPipelineTest::class),
        ])
        ->thenReturn();

    expect(is_a($passable, PipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(2);
});

it('can run as a pipe in a pipeline with only one explicit container resolved instance at the bottom of the stack', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            AsPipelineTest::class, // implicit container resolved instance
            app()->make(AsPipelineTest::class), // explicit container resolved instance
        ])
        ->thenReturn();

    expect(is_a($passable, PipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(2);
});

it('cannot run as a pipe in a pipeline with an explicit container resolved instance in the middle of the stack', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            AsPipelineTest::class, // implicit container resolved instance
            app()->make(AsPipelineTest::class), // explicit container resolved instance
            AsPipelineTest::class, // implicit container resolved instance
            AsPipelineTest::class, // implicit container resolved instance
        ])
        ->thenReturn();

    expect(is_a($passable, PipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(2);
});

it('cannot run as a pipe in a pipeline as an standalone instance', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            new AsPipelineTest, // standalone instance
            AsPipelineTest::class, // implicit container resolved instance
            app()->make(AsPipelineTest::class), // explicit container resolved instance
        ])
        ->thenReturn();

    expect(is_null($passable))->toBe(true);
});
