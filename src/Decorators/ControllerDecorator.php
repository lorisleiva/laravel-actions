<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Support\Str;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\DecorateActions;
use Lorisleiva\Actions\Concerns\WithAttributes;
use ReflectionMethod;
use ReflectionNamedType;
use Illuminate\Support\Collection;

class ControllerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    protected Container $container;

    protected Route $route;

    protected array $middleware = [];

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

        $this->container->instance(Route::class, $this->route);

        $parameters = $this->route->parametersWithoutNulls();
        $parameters = $this->resolveRouteModelBindings($parameters, $method);

        $arguments = $this->resolveClassMethodDependencies(
            $parameters,
            $this->action,
            $method
        );

        return $this->action->{$method}(...array_values($arguments));
    }

    protected function resolveRouteModelBindings(array $parameters, string $method): array
    {
        $reflection = new ReflectionMethod($this->action, $method);
        $reflectionParameters = Collection::make($reflection->getParameters())->keyBy(
            fn($p) => $p->getName()
        );

        foreach ($parameters as $key => $value) {
            if (is_object($value) || ! $reflectionParameters->has($key)) {
                continue;
            }

            $type = $reflectionParameters->get($key)->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $modelClass = $type->getName();

            if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                $bindingField = $this->getBindingFieldForRouteParameter($key);
                $parameters[$key] = $this->resolveModel($modelClass, $value, $bindingField);
            }
        }

        return $parameters;
    }

    protected function getBindingFieldForRouteParameter(string $parameter): ?string
    {
        if (property_exists($this->route, 'bindingFields') && isset($this->route->bindingFields[$parameter])) {
            return $this->route->bindingFields[$parameter];
        }

        if (method_exists($this->route, 'bindingFieldFor')) {
            return $this->route->bindingFieldFor($parameter);
        }

        return null;
    }

    protected function resolveModel(string $modelClass, mixed $value, ?string $bindingField = null): Model
    {
        if ($bindingField) {
            return $modelClass::where($bindingField, $value)->firstOrFail();
        }

        return $modelClass::findOrFail($value);
    }
}
