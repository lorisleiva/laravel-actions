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

    /**
     * Sadly necessary to trigger middleware resolution on the controller decorator since 9.x.
     * @see https://github.com/laravel/framework/pull/40397#issuecomment-1022042544
     * @return array
     */
    public function getMiddleware()
    {
        return [];
    }
}
