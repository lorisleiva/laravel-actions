<?php

namespace Lorisleiva\Actions\Concerns;

trait DecorateActions
{
    /** @var mixed */
    protected $action;

    public function setAction($action): self
    {
        $this->action = $action;

        return $this;
    }

    protected function hasTrait(string $trait): bool
    {
        return in_array($trait, class_uses_recursive($this->action));
    }

    protected function hasProperty(string $property): bool
    {
        return property_exists($this->action, $property);
    }

    protected function getProperty(string $property)
    {
        return $this->action->{$property};
    }

    protected function hasMethod(string $method): bool
    {
        return method_exists($this->action, $method);
    }

    protected function callMethod(string $method, array $parameters = [])
    {
        return call_user_func_array([$this->action, $method], $parameters);
    }

    protected function resolveAndCallMethod(string $method, array $extraArguments = [])
    {
        return app()->call([$this->action, $method], $extraArguments);
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
