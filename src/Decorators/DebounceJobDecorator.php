<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\DebounceFor;
use ReflectionClass;

class DebounceJobDecorator extends JobDecorator
{
    public ?int $debounceFor = null;
    public ?int $maxWait = null;

    /**
     * Resolve the DebounceFor attribute from the action class, its parents or
     * their traits, mirroring Laravel's ReadsClassAttributes::getAttributeInstance().
     */
    public static function debounceAttributeFor(string $actionClass): ?DebounceFor
    {
        if (! class_exists(DebounceFor::class)) {
            return null;
        }

        $reflection = new ReflectionClass($actionClass);

        do {
            foreach ([$reflection, ...$reflection->getTraits()] as $source) {
                if ($attributes = $source->getAttributes(DebounceFor::class)) {
                    return $attributes[0]->newInstance();
                }
            }
        } while ($reflection = $reflection->getParentClass());

        return null;
    }

    protected function constructed(): void
    {
        if ($attribute = static::debounceAttributeFor($this->actionClass)) {
            $this->debounceFor = $attribute->debounceFor;
            $this->maxWait = $attribute->maxWait;
        }

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
