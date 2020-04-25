<?php

namespace Lorisleiva\Actions\Concerns;

use ReflectionClass;
use ReflectionProperty;

trait RunsAsListener
{
    public function runAsListener()
    {
        $this->runningAs = 'listener';

        $this->fill($this->resolveAttributesFromEvent(...func_get_args()));

        return $this->run();
    }

    public function resolveAttributesFromEvent($event = null)
    {
        if (method_exists($this, 'getAttributesFromEvent')) {
            return $this->getAttributesFromEvent(...func_get_args());
        }

        if ($event && is_object($event)) {
            return $this->getPublicPropertiesOfEvent($event);
        }

        return [];
    }

    protected function getPublicPropertiesOfEvent($event)
    {
        $class = new ReflectionClass(get_class($event));
        $attributes = [];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes[$property->name] = $property->getValue($event);
        }

        return $attributes;
    }
}
