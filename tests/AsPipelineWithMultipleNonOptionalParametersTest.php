<?php

namespace Lorisleiva\Actions\Tests;

use ArgumentCountError;
use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;

class AsPipelineWithMultipleNonOptionalParametersTest
{
    use AsAction;

    public function handle(AsPipelinePassable $passable, int $nonOptionalAdditionalParameter): void
    {
        $passable->increment();
    }

    public function asPipeline(AsPipelinePassable $passable): AsPipelinePassable
    {
        $this->handle($passable);

        return $passable;
    }
}

it('cannot run as a pipe in a pipeline expecting multiple non-optional parameters', function () {
    $passable = Pipeline::send(new AsPipelinePassable)
        ->through([
            AsPipelineWithMultipleNonOptionalParametersTest::class,
        ])
        ->thenReturn();
})->throws(ArgumentCountError::class);
