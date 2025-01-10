<?php

namespace Lorisleiva\Actions\Concerns;

trait AsPipeline
{
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
     *
     * Also, this logic is implemented in the trait rather than the decorator
     * to afford some flexibility to consuming projects, should the wish to
     * implement their own logic in their Action classes directly.
     */
    public function asPipeline(mixed ...$arguments): mixed
    {
        $passable = array_shift($arguments);
        $closure = array_pop($arguments);

        $returned = $this->handle($passable);

        if (! is_null($returned)) {
            return $closure($returned);
        } else {
            return $closure($passable);
        }
    }
}
