<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;

class AsPipelineTest
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

it('can run as a pipe in a pipeline, with an explicit asPipeline method', function () {
    $passable = Pipeline::send(new AsPipelinePassable)
        ->through([
            AsPipelineTest::class,
            AsPipelineTest::class,
            AsPipelineTest::class,
            AsPipelineTest::class,
        ])
        ->thenReturn();

    expect(is_a($passable, AsPipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(4);
});

it('can run with an arbitrary via method configured on Pipeline', function () {
    $passable = Pipeline::send(new AsPipelinePassable)
        ->via('arbitraryMethodThatDoesNotExistOnTheAction')
        ->through([
            AsPipelineTest::class,
            AsPipelineTest::class,
            AsPipelineTest::class,
            AsPipelineTest::class,
        ])
        ->thenReturn();

    expect(is_a($passable, AsPipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(4);
});
