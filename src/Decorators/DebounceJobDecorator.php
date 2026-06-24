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

    protected function constructed(): void
    {
        $attributes = (new ReflectionClass($this->action))->getAttributes(DebounceFor::class);

        if (! empty($attributes)) {
            $instance = $attributes[0]->newInstance();
            $this->debounceFor = $instance->debounceFor;
            $this->maxWait = $instance->maxWait;
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
