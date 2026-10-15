<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Support\Traits\ReadsClassAttributes;

class DebounceJobDecorator extends JobDecorator
{
    public ?int $debounceFor = null;
    public ?int $maxWait = null;

    /**
     * Determine if the action is debounced, either by the DebounceFor attribute
     * or by a jobDebounceFor property or getJobDebounceFor() method, just as
     * Laravel debounces a job that only has a debounceFor property.
     */
    public static function shouldDebounce(string $actionClass): bool
    {
        if (! class_exists(DebounceFor::class)) {
            return false;
        }

        return method_exists($actionClass, 'getJobDebounceFor')
            || property_exists($actionClass, 'jobDebounceFor')
            || static::debounceAttributeFor($actionClass) !== null;
    }

    /**
     * Resolve the DebounceFor attribute from the action class, its parents or
     * their traits, using the same lookup Laravel uses for debounced jobs.
     *
     * The guard must come first: ReadsClassAttributes doesn't exist on older Laravel,
     * and the anonymous class only resolves its trait when it is instantiated.
     *
     * Laravel types getAttributeInstance()'s $target as an object, but only hands
     * it to ReflectionClass, which accepts a class name too. A class name is all
     * makeJob() has when choosing the decorator, before the action is built, so
     * the resulting PHPStan error is ignored in phpstan.neon.dist.
     */
    protected static function debounceAttributeFor(string $actionClass): ?DebounceFor
    {
        if (! class_exists(DebounceFor::class)) {
            return null;
        }

        return (new class {
            use ReadsClassAttributes { getAttributeInstance as public; }
        })->getAttributeInstance($actionClass, DebounceFor::class);
    }

    protected function constructed(): void
    {
        $attribute = static::debounceAttributeFor($this->actionClass);

        $this->debounceFor = $this->fromActionMethodOrProperty(
            'getJobDebounceFor',
            'jobDebounceFor',
            $attribute?->debounceFor,
            $this->parameters
        );

        // Like Laravel, maxWait can only be set through the attribute.
        $this->maxWait = $attribute?->maxWait;

        parent::constructed();
    }

    public function debounceId(): string
    {
        return (string) $this->fromActionMethodOrProperty(
            'getJobDebounceId',
            'jobDebounceId',
            '',
            $this->parameters
        );
    }

    public function debounceVia()
    {
        return $this->fromActionMethod('getJobDebounceVia', $this->parameters, function () {
            return Container::getInstance()->make(Cache::class);
        });
    }
}
