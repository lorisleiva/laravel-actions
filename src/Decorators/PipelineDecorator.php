<?php

namespace Lorisleiva\Actions\Decorators;

use Closure;
use Exception;
use Lorisleiva\Actions\Concerns\DecorateActions;

class PipelineDecorator
{
    use DecorateActions;

    public function __construct($action)
    {
        $this->setAction($action);
    }

    public function __invoke(mixed ...$arguments): mixed
    {
        return $this->handleFromAnyMethod(...$arguments);
    }

    public function handle(mixed ...$arguments): mixed
    {
        return $this->handleFromAnyMethod(...$arguments);
    }

    protected function handleFromAnyMethod(mixed ...$arguments): mixed
    {
        if ($this->hasMethod('asPipeline')) {
            return $this->resolveAndCallMethod('asPipeline', $arguments);
        }
    }
}
