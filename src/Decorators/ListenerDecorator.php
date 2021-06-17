<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Routing\RouteDependencyResolverTrait;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ListenerDecorator
{
    use RouteDependencyResolverTrait;
    use DecorateActions;

    public function __construct($action)
    {
        $this->setAction($action);
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
