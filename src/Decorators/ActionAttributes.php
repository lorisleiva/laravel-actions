<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Support\Traits\ReadsClassAttributes;

/**
 * Reads queue attributes off actions using Laravel's own lookup, so they
 * resolve from parents and traits, and take precedence over properties,
 * exactly as they do on native jobs.
 *
 * ReadsClassAttributes only exists from Laravel 13, so this class must not
 * be loaded before checking that the trait exists.
 */
class ActionAttributes
{
    use ReadsClassAttributes;

    /**
     * Laravel types getAttributeInstance()'s $target as an object, but only hands
     * it to ReflectionClass, which accepts a class name too. A class name is all
     * makeJob() has when choosing the decorator, before the action is built, so
     * the resulting PHPStan error is ignored in phpstan.neon.dist.
     *
     * @template T of object
     * @param class-string $actionClass
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    public function instance(string $actionClass, string $attributeClass): ?object
    {
        if (! class_exists($attributeClass)) {
            return null;
        }

        return $this->getAttributeInstance($actionClass, $attributeClass);
    }

    /**
     * @param class-string $actionClass
     * @param class-string $attributeClass
     */
    public function value(string $actionClass, string $attributeClass): mixed
    {
        $attribute = $this->instance($actionClass, $attributeClass);

        return $attribute ? $this->extractAttributeValue($attribute) : null;
    }

    /**
     * @param class-string $attributeClass
     */
    public function valueOrProperty(object $action, string $attributeClass, string $property): mixed
    {
        // Some attributes, like CountCrashesAsExceptions, only exist on later 13.x releases.
        if (! class_exists($attributeClass)) {
            return $action->{$property} ?? null;
        }

        return $this->getAttributeValue($action, $attributeClass, $property);
    }
}
