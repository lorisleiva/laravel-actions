<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Contracts\Container\Container;

trait DecorateActions
{
    /** @var Container|null */
    protected $container;

    /** @var mixed */
    protected $action;

    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    protected function hasProperty($property)
    {
        return property_exists($this->action, $property);
    }

    protected function getProperty($property)
    {
        return $this->action->{$property};
    }

    protected function hasMethod($method)
    {
        return method_exists($this->action, $method);
    }

    protected function callMethod($method, $parameters = [])
    {
        return call_user_func_array([$this->action, $method], $parameters);
    }

    protected function resolveAndCallMethod($method, $extraArguments = [])
    {
        return $this->container->call([$this->action, $method], $extraArguments);
    }

    protected function fromActionMethod(string $method, array $methodParameters = [], $default = null)
    {
        return $this->hasMethod($method)
            ? $this->callMethod($method, $methodParameters)
            : value($default);
    }

    protected function fromActionProperty(string $property, $default = null)
    {
        return $this->hasProperty($property)
            ? $this->getProperty($property)
            : value($default);
    }

    protected function fromActionMethodOrProperty(string $method, string $property, $default = null, array $methodParameters = [])
    {
        if ($this->hasMethod($method)) {
            return $this->callMethod($method, $methodParameters);
        }

        if ($this->hasProperty($property)) {
            return $this->getProperty($property);
        }

        return value($default);
    }
}
