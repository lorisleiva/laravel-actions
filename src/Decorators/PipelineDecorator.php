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

    /**
     * Typical pipeline behavior expects two things:
     *
     *     1)  The pipe class to expect a single incoming parameter (along with
     *         a closure) and single return value.
     *     2)  The pipe class to be aware of the next closure and determine what
     *         should be passed into the next pipe.
     *
     * Because of these expectations, this behavior is asserting two opinions:
     *
     *     1)  Regardless of the number of parameters provided to the asPipeline
     *         method implemented here, only the first will be supplied to the
     *         invoked Action.
     *     2)  If the invoked Action does not return anything, then the next
     *         closure will be supplied the same parameter. However, if the
     *         invoked action does return a non-null value, that value will
     *         be supplied to the next closure.
     */
    protected function handleFromAnyMethod(mixed ...$arguments): mixed
    {
        $passable = array_shift($arguments);
        $closure = array_pop($arguments);
        $returned = null;

        if ($this->hasMethod('asPipeline')) {
            $returned = $this->callMethod('asPipeline', [$passable]);
        } elseif ($this->hasMethod('handle')) {
            $returned = $this->callMethod('handle', [$passable]);
        }

        return $closure($returned ?? $passable);
    }
}
