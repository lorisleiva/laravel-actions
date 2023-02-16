<?php

namespace Lorisleiva\Actions\Concerns;

use Lorisleiva\Actions\Decorators\ControllerDecorator;
use Illuminate\Support\Facades\Route;

trait AsController
{
    /**
     * @see static::handle()
     * @param mixed ...$arguments
     * @return mixed
     */
    public function __invoke(...$arguments)
    {
        return (new ControllerDecorator($this, Route::current()))->execute();
    }

    /**
     * This empty method is required to enable controller middleware on the action.
     * @see https://github.com/lorisleiva/laravel-actions/issues/199
     * @return array
     */
    public function getMiddleware()
    {
        return [];
    }
}
