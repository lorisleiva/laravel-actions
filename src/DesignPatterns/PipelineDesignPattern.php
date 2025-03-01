<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Illuminate\Pipeline\Pipeline;
use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\AsPipeline;
use Lorisleiva\Actions\Decorators\PipelineDecorator;
use Lorisleiva\Actions\DesignPatterns\DesignPattern;

class PipelineDesignPattern extends DesignPattern
{
    public function getTrait(): string
    {
        return AsPipeline::class;
    }

    public function recognizeFrame(BacktraceFrame $frame): bool
    {
        return $frame->matches(Pipeline::class, 'Illuminate\Pipeline\{closure}')
            || $frame->matches(Pipeline::class, '{closure:{closure:Illuminate\Pipeline\Pipeline::carry():184}:185}');
    }

    public function decorate($instance, BacktraceFrame $frame)
    {
        return app(PipelineDecorator::class, ['action' => $instance]);
    }
}
