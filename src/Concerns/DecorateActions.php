<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Contracts\Container\Container;

trait DecorateActions
{
    /** @var Container */
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

    protected function resolveAndCall($method, $extraArguments = [])
    {
        return $this->container->call([$this->action, $method], $extraArguments);
    }
}
