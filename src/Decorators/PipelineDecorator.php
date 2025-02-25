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
        $passable = array_shift($arguments);
        $closure = array_pop($arguments);

        $method = $this->hasMethod('asPipeline') ? 'asPipeline' : 'handle';

        return $closure($this->callMethod($method, [$passable]) ?? $passable);
    }
}
