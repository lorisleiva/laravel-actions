<?php

namespace Lorisleiva\Actions\Tests;

use ArgumentCountError;
use Illuminate\Support\Facades\Pipeline;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Tests\Stubs\PipelinePassable;
use TypeError;

class AsPipelineWithMultipleNonOptionalParametersTest
{
    use AsAction;

    public function handle(PipelinePassable $passable, int $nonOptionalAdditionalParameter): void
    {
        $passable->increment();
    }

    public function asPipeline(PipelinePassable $passable): PipelinePassable
    {
        $this->handle($passable);

        return $passable;
    }
}

it('cannot run as a pipe in a pipeline expecting multiple non-optional parameters', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            AsPipelineWithMultipleNonOptionalParametersTest::class,
            app()->make(AsPipelineWithMultipleNonOptionalParametersTest::class),
        ])
        ->thenReturn();
})->throws(ArgumentCountError::class);

it('cannot run as a pipe in a pipeline as an explicit container resolved instance preceding an implicit container resolved instance', function () {
    $passable = Pipeline::send(new PipelinePassable)
        ->through([
            app()->make(AsPipelineWithMultipleNonOptionalParametersTest::class),
            AsPipelineWithMultipleNonOptionalParametersTest::class,
        ])
        ->thenReturn();
})->throws(TypeError::class);
