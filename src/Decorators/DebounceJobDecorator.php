<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\DebounceFor;

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

    protected static function debounceAttributeFor(string $actionClass): ?DebounceFor
    {
        return static::actionAttribute($actionClass, DebounceFor::class);
    }

    /**
     * A debounced job runs once the debounce window closes, so Laravel ignores
     * #[Delay]: the debounce lock only sets the delay while it is still null.
     */
    protected function delayFromAction(): mixed
    {
        return null;
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
