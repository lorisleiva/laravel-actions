<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionParameter;

trait ResolvesMethodDependencies
{
    protected function resolveAndCall($instance, $method, $extras = [])
    {
        $parameters = $this->resolveMethodDependencies($instance, $method, $extras);

        return $instance->{$method}(...$parameters);
    }

    protected function resolveMethodDependencies($instance, $method, $extras = []): array
    {
        if (! method_exists($instance, $method)) {
            return [];
        }

        $reflector = new ReflectionMethod($instance, $method);

        $handler = function ($parameter) use ($extras) {
            return $this->resolveDependency($parameter, $extras);
        };

        return array_map($handler, $reflector->getParameters());
    }

    protected function resolveDependency(ReflectionParameter $parameter, $extras = [])
    {
        list($key, $value) = $this->findAttributeFromParameter($parameter->name, $extras);
        $class = $parameter->getClass();

        if ($key && (! $class || $value instanceof $class->name)) {
            return $value;
        }

        if ($key && $class && is_subclass_of($class->name, UrlRoutable::class)) {
            return $this->resolveRouteBinding($class->name, $key, $value, $parameter->allowsNull());
        }

        if ($class && ! $parameter->allowsNull()) {
            return $this->resolveContainerDependency($class->name, $key);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
    }

    protected function resolveRouteBinding($class, $key, $value, $nullable = false)
    {
        $instance = app($class);
        $route = $this->runningAs('controller') ? $this->request->route() : null;
        $field = $route && method_exists($route, 'bindingFieldFor') ? $route->bindingFieldFor($key) : null;
        $parent = $route && method_exists($route, 'parentOfParameter') ? $route->parentOfParameter($key) : null;

        if ($parent && $field && $parent instanceof UrlRoutable) {
            $model = $parent->resolveChildRouteBinding($key, $value, $field);
        } else {
            $model = $instance->resolveRouteBinding($value, $field);
        }

        if (! $model && ! $nullable) {
            throw (new ModelNotFoundException)->setModel(get_class($instance), [$value]);
        }

        optional($route)->setParameter($key, $model);
        $this->updateAttributeWithResolvedInstance($key, $model);

        return $model;
    }

    protected function resolveContainerDependency($class, $key)
    {
        $instance = app($class);

        if ($key) {
            $this->updateAttributeWithResolvedInstance($key, $instance);
        }

        return $instance;
    }

    protected function findAttributeFromParameter($name, $extras = []): array
    {
        $routeAttributes = $this->runningAs('controller') ? $this->getAttributesFromRoute($this->request) : [];
        $attributes = array_merge($this->attributes, $routeAttributes, $extras);

        if (array_key_exists($name, $attributes)) {
            return [$name, $attributes[$name]];
        }

        if (array_key_exists($snakedName = Str::snake($name), $attributes)) {
            return [$snakedName, $attributes[$snakedName]];
        }

        return [null, null];
    }

    protected function updateAttributeWithResolvedInstance($key, $instance): void
    {
        if ($this->runningAs('controller') && $this->request->has($key)) {
            return;
        }

        $this->attributes[$key] = $instance;
    }
}
