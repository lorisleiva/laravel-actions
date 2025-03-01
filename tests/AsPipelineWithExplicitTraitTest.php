<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsPipeline;
use Lorisleiva\Actions\Tests\Stubs\PipelinePassable;

class AsPipelineWithExplicitTraitTest
{
    use AsPipeline;

    public function handle(PipelinePassable $passable): void
    {
        $passable->increment();
    }
}

it('can run as a pipe in a pipeline, with explicit trait, without asPipeline method', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            AsPipelineWithExplicitTraitTest::class,
            app()->make(AsPipelineWithExplicitTraitTest::class),
        ])
        ->thenReturn();

    expect(is_a($passable, PipelinePassable::class))->toBe(true);
    expect($passable->count)->toBe(2);
});
