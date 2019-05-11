<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionParameter;

trait ResolvesMethodDependencies
{
    protected function resolveMethodDependencies($instance, $method)
    {
        if (! method_exists($instance, $method)) {
            return [];
        }

        $reflector = new ReflectionMethod($instance, $method);

        $handler = function ($parameter) {
            return $this->resolveDependency($parameter);
        };

        return array_map($handler, $reflector->getParameters());
    }

    protected function resolveDependency(ReflectionParameter $parameter)
    {   
        list($key, $value) = $this->findAttributeFromParameter($parameter->name);
        $class = $parameter->getClass();

        if ($key && (! $class || $value instanceof $class->name)) {
            return $value;
        }

        if ($class) {
            return $this->resolveContainerDependency($class->name, $key, $value);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
    }

    protected function resolveContainerDependency($class, $key, $value)
    {
        $instance = app($class);

        if ($key && method_exists($instance, 'resolveRouteBinding')) {
            $instance = $this->resolveRouteBinding($instance, $value);
        }

        if ($key) {
            $this->attributes[$key] = $instance;
        }

        return $instance;
    }

    protected function resolveRouteBinding($instance, $value)
    {
        if (! $model = $instance->resolveRouteBinding($value)) {
            throw (new ModelNotFoundException)->setModel(get_class($instance));
        }

        return $model;
    }

    protected function findAttributeFromParameter($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return [$name, $this->attributes[$name]];
        }
        if (array_key_exists($snakedName = Str::snake($name), $this->attributes)) {
            return [$snakedName, $this->attributes[$snakedName]];
        }
    }
}