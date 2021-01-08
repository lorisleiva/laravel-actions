<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ListenerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    public function __construct($action, Container $container)
    {
        $this->setAction($action);
        $this->setContainer($container);
    }

    public function handle(...$arguments)
    {
        if ($this->hasMethod('asListener')) {
            return $this->resolveFromArgumentsAndCall('asListener', $arguments);
        }

        if ($this->hasMethod('handle')) {
            return $this->resolveFromArgumentsAndCall('handle', $arguments);
        }
    }

    protected function resolveFromArgumentsAndCall($method, $arguments)
    {
        $arguments = $this->resolveClassMethodDependencies(
            $arguments,
            $this->action,
            $method
        );

        return $this->action->{$method}(...array_values($arguments));
    }
}
