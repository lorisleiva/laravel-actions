<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsPipeline;

class AsPipelineWithExplicitTraitTest
{
    use AsPipeline;

    public function handle(AsPipelinePassable $passable): void
    {
        $passable->increment();
    }
}

it('can run as a pipe in a pipeline, with explicit trait, without asPipeline method', function () {
    $passable = Pipeline::send(new AsPipelinePassable)
        ->through([
            AsPipelineWithExplicitTraitTest::class,
            AsPipelineWithExplicitTraitTest::class,
            AsPipelineWithExplicitTraitTest::class,
            AsPipelineWithExplicitTraitTest::class,
        ])
        ->thenReturn();

    expect(is_a($passable, AsPipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(4);
});
