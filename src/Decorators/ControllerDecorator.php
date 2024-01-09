<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Support\Str;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\DecorateActions;
use Lorisleiva\Actions\Concerns\WithAttributes;

class ControllerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    /** @var Container */
    protected Container $container;

    /** @var Route */
    protected Route $route;

    /** @var array */
    protected $middleware = [];

    /** @var bool */
    protected bool $executedAtLeastOne = false;

    public function __construct($action, Route $route)
    {
        $this->container = Container::getInstance();
        $this->route = $route;
        $this->setAction($action);
        $this->replaceRouteMethod();

        if ($this->hasMethod('getControllerMiddleware')) {
            $this->middleware = $this->resolveAndCallMethod('getControllerMiddleware');
        }
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getMiddleware(): array
    {
        return array_map(function ($middleware) {
            return [
                'middleware' => $middleware,
                'options' => [],
            ];
        }, $this->middleware);
    }

    public function callAction($method, $parameters)
    {
        return $this->__invoke($method);
    }

    public function __invoke(string $method)
    {
        $this->refreshAction();
        $request = $this->refreshRequest();

        if ($this->shouldValidateRequest($method)) {
            $request->validate();
        }

        $response = $this->run($method);

        if ($this->hasMethod('jsonResponse') && $request->expectsJson()) {
            $response = $this->callMethod('jsonResponse', [$response, $request]);
        } elseif ($this->hasMethod('htmlResponse') && ! $request->expectsJson()) {
            $response = $this->callMethod('htmlResponse', [$response, $request]);
        }

        return $response;
    }

    protected function refreshAction(): void
    {
        if ($this->executedAtLeastOne) {
            $this->setAction(app(get_class($this->action)));
        }

        $this->executedAtLeastOne = true;
    }

    protected function refreshRequest(): ActionRequest
    {
        app()->forgetInstance(ActionRequest::class);

        /** @var ActionRequest $request */
        $request = app(ActionRequest::class);
        $request->setAction($this->action);
        app()->instance(ActionRequest::class, $request);

        return $request;
    }

    protected function replaceRouteMethod(): void
    {
        if (! isset($this->route->action['uses'])) {
            return;
        }

        $currentMethod = Str::afterLast($this->route->action['uses'], '@');
        $newMethod = $this->getDefaultRouteMethod();

        if ($currentMethod !== '__invoke' || $currentMethod === $newMethod) {
            return;
        }

        $this->route->action['uses'] = (string) Str::of($this->route->action['uses'])
            ->beforeLast('@')
            ->append('@' . $newMethod);
    }

    protected function getDefaultRouteMethod(): string
    {
        if ($this->hasMethod('asController')) {
            return 'asController';
        }

        return $this->hasMethod('handle') ? 'handle' : '__invoke';
    }

    protected function isExplicitMethod(string $method): bool
    {
        return ! in_array($method, ['asController', 'handle', '__invoke']);
    }

    protected function run(string $method)
    {
        if ($this->hasMethod($method)) {
            return $this->resolveFromRouteAndCall($method);
        }
    }

    protected function shouldValidateRequest(string $method): bool
    {
        return $this->hasAnyValidationMethod()
            && ! $this->isExplicitMethod($method)
            && ! $this->hasTrait(WithAttributes::class);
    }

    protected function hasAnyValidationMethod(): bool
    {
        return $this->hasMethod('authorize')
            || $this->hasMethod('rules')
            || $this->hasMethod('withValidator')
            || $this->hasMethod('afterValidator')
            || $this->hasMethod('getValidator');
    }

    protected function resolveFromRouteAndCall($method)
    {
        $this->container = Container::getInstance();

        $arguments = $this->resolveClassMethodDependencies(
            $this->route->parametersWithoutNulls(),
            $this->action,
            $method
        );

        return $this->action->{$method}(...array_values($arguments));
    }
}
