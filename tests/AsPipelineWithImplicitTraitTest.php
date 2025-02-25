<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;

class AsPipelineWithImplicitTraitTest
{
    use AsAction;

    public function handle(AsPipelinePassable $passable): void
    {
        $passable->increment();
    }
}

it('can run as a pipe in a pipeline, with implicit trait, without asPipeline method', function () {
    $passable = Pipeline::send(new AsPipelinePassable)
        ->through([
            AsPipelineWithImplicitTraitTest::class,
            AsPipelineWithImplicitTraitTest::class,
            AsPipelineWithImplicitTraitTest::class,
            AsPipelineWithImplicitTraitTest::class,
        ])
        ->thenReturn();

    expect(is_a($passable, AsPipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(4);
});
