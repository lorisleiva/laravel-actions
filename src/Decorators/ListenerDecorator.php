<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ListenerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    public function __construct($action)
    {
        $this->setAction($action);
        $this->container = new Container;
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

    public function shouldQueue(...$arguments)
    {
        if ($this->hasMethod('shouldQueue')) {
            return $this->resolveFromArgumentsAndCall('shouldQueue', $arguments);
        }

        return true;
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
