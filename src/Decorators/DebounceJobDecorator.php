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
     * Resolve the DebounceFor attribute from the action class, its parents or
     * their traits, using the same lookup Laravel uses for debounced jobs.
     *
     * The guard must come first: ReadsClassAttributes only exists on Laravel 13.6+,
     * and the anonymous class only resolves its trait when it is instantiated.
     */
    public static function debounceAttributeFor(string $actionClass): ?DebounceFor
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
