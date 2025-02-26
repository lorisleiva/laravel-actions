<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Tests\Stubs\PipelinePassable;

class AsPipelineWithImplicitTraitTest
{
    use AsAction;

    public function handle(PipelinePassable $passable): void
    {
        $passable->increment();
    }
}

it('can run as a pipe in a pipeline, with implicit trait, without asPipeline method', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            AsPipelineWithImplicitTraitTest::class,
            app()->make(AsPipelineWithImplicitTraitTest::class),
        ])
        ->thenReturn();

    expect(is_a($passable, PipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(2);
});
