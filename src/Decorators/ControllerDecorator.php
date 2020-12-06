<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ControllerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    /** @var array */
    protected $middleware = [];

    public function __construct($action, Container $container)
    {
        $this->setAction($action);
        $this->setContainer($container);

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

        $result = $this->run($request);

        $this->container->forgetInstance(ActionRequest::class);

        return $result;
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
}
