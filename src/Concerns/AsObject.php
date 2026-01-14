<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Support\Fluent;
use ReflectionMethod;

trait AsObject
{
    use RouteDependencyResolverTrait;

    /**
     * @return static
     */
    public static function make()
    {
        return app(static::class);
    }

    /**
     * @see static::handle()
     */
    public static function run(mixed ...$arguments): mixed
    {
        $instance = static::make();
        
        if (static::shouldResolveModelBindings()) {
            $arguments = static::resolveModelBindings($instance, 'handle', $arguments);
        } else {
            $arguments = static::convertToNamedParameters($instance, 'handle', $arguments);
        }

        return app()->call([$instance, 'handle'], $arguments);
    }

    public static function runIf(bool $boolean, mixed ...$arguments): mixed
    {
        return $boolean ? static::run(...$arguments) : new Fluent;
    }

    public static function runUnless(bool $boolean, mixed ...$arguments): mixed
    {
        return static::runIf(! $boolean, ...$arguments);
    }

    /**
     * Override to enable automatic model resolution from scalar values.
     * 
     * @return bool
     */
    protected static function shouldResolveModelBindings(): bool
    {
        return false;
    }

    protected static function convertToNamedParameters(object $instance, string $method, array $arguments): array
    {
        if (! method_exists($instance, $method)) {
            return $arguments;
        }

        $reflection = new ReflectionMethod($instance, $method);
        $parameters = $reflection->getParameters();
        $resolved = [];

        foreach ($parameters as $index => $parameter) {
            if (! array_key_exists($index, $arguments)) {
                continue;
            }

            $resolved[$parameter->getName()] = $arguments[$index];
        }

        return $resolved;
    }

    protected static function resolveModelBindings(object $instance, string $method, array $arguments): array
    {
        if (! method_exists($instance, $method)) {
            return $arguments;
        }

        $reflection = new ReflectionMethod($instance, $method);
        $parameters = $reflection->getParameters();
        $resolved = [];

        foreach ($parameters as $index => $parameter) {
            if (! array_key_exists($index, $arguments)) {
                continue;
            }

            $value = $arguments[$index];

            if (is_object($value)) {
                $resolved[$parameter->getName()] = $value;
                continue;
            }

            $type = $parameter->getType();
            if ($type && ! $type->isBuiltin() && class_exists($type->getName())) {
                $typeName = $type->getName();

                if (is_subclass_of($typeName, Model::class)) {
                    if (is_scalar($value) && $value !== null) {
                        $resolved[$parameter->getName()] = $typeName::findOrFail($value);
                        continue;
                    }
                }
            }

            $resolved[$parameter->getName()] = $value;
        }

        return $resolved;
    }
}
