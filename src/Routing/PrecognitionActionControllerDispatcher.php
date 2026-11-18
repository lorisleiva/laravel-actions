<?php

namespace Lorisleiva\Actions\Routing;

use Illuminate\Foundation\Routing\PrecognitionControllerDispatcher;
use Illuminate\Routing\Route;
use Lorisleiva\Actions\Decorators\ControllerDecorator;

class PrecognitionActionControllerDispatcher extends PrecognitionControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return void
     */
    public function dispatch(Route $route, $controller, $method)
    {
        // In order to work with Laravel Actions
        // we need the controller class from the route action
        if ($controller instanceof ControllerDecorator) {
            $controller = $route->action["controller"];
        }
        parent::dispatch($route, $controller, $method);
    }
}
