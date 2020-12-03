<?php

namespace Lorisleiva\Actions\Concerns;

trait AsController
{
    /**
     * @see static::handle()
     * @param mixed ...$arguments
     * @return mixed
     */
    public function __invoke(...$arguments)
    {
        return $this->handle(...$arguments);
    }
}
