<?php

namespace Lorisleiva\Actions\Concerns;

/**
 * @method array getControllerMiddleware()
 * @method \Illuminate\Http\Resources\Json\JsonResource jsonResponse()
 * @method \Illuminate\Http\Response htmlResponse()
 * @method void routes(\Illuminate\Routing\Router $router)
 * @method \Illuminate\Http\Response asController()
  */
trait AsController
{
    /**
     * @see static::handle()
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        return $this->handle(...$arguments);
    }

    /**
     * This empty method is required to enable controller middleware on the action.
     * @see https://github.com/lorisleiva/laravel-actions/issues/199
     */
    public function getMiddleware(): array
    {
        return [];
    }
}
