<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\BacktraceFrame;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ControllerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    /** @var Route */
    protected Route $route;

    /** @var array */
    protected $middleware = [];

    public function __construct($action, Container $container)
    {
        $this->setAction($action);
        $this->setContainer($container);
        $this->route = $this->findRouteFromBacktrace();

        if ($this->hasMethod('getControllerMiddleware')) {
            $this->middleware = $this->resolveAndCallMethod('getControllerMiddleware');
        }
    }

    public function getMiddleware()
    {
        return array_map(function ($middleware) {
            return [
                'middleware' => $middleware,
                'options' => [],
            ];
        }, $this->middleware);
    }

    public function __invoke(ActionRequest $request)
    {
        $this->container->instance(ActionRequest::class, $request);
        $request->setAction($this->action);

        if ($this->shouldValidate()) {
            $request->resolve();
        }

        $response = $this->run($request);

        if ($this->hasMethod('jsonResponse') && $request->expectsJson()) {
            $response = $this->callMethod('jsonResponse', [$response, $request]);
        } elseif ($this->hasMethod('htmlResponse') && ! $request->expectsJson()) {
            $response = $this->callMethod('htmlResponse', [$response, $request]);
        }

        $this->container->forgetInstance(ActionRequest::class);

        return $response;
    }

    protected function run(ActionRequest $request)
    {
        if ($this->hasMethod('asController')) {
            return $this->resolveFromRouteAndCall('asController', $request);
        }

        if ($this->hasMethod('handle')) {
            return $this->resolveFromRouteAndCall('handle', $request);
        }
    }

    protected function shouldValidate()
    {
        return $this->hasMethod('authorize')
            || $this->hasMethod('rules')
            || $this->hasMethod('withValidator')
            || $this->hasMethod('afterValidator')
            || $this->hasMethod('getValidator');
    }

    protected function resolveFromRouteAndCall($method, ActionRequest $request)
    {
        $arguments = $this->resolveClassMethodDependencies(
            $request->route()->parametersWithoutNulls(),
            $this->action,
            $method
        );

        return $this->action->{$method}(...array_values($arguments));
    }

    protected function findRouteFromBacktrace(): Route
    {
        $backtraceOptions = DEBUG_BACKTRACE_PROVIDE_OBJECT
            | DEBUG_BACKTRACE_IGNORE_ARGS;

        foreach (debug_backtrace($backtraceOptions, 20) as $frame) {
            $frame = new BacktraceFrame($frame);

            if ($frame->instanceOf(Route::class)) {
                return $frame->getObject();
            }
        }
    }
}
