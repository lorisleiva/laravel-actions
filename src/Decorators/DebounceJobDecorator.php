<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Attributes\DebounceFor;
use ReflectionClass;

#[DebounceFor(0)]
class DebounceJobDecorator extends JobDecorator
{
    public ?int $debounceFor = null;

    protected function constructed(): void
    {
        if (class_exists(DebounceFor::class)) {
            $attributes = (new ReflectionClass($this->action))->getAttributes(DebounceFor::class);

            if (! empty($attributes)) {
                $this->debounceFor = $attributes[0]->newInstance()->debounceFor;
            }
        }

        parent::constructed();
    }

    public function debounceVia()
    {
        return $this->fromActionMethod('getJobDebounceVia', $this->parameters, function () {
            return Container::getInstance()->make(Cache::class);
        });
    }
}
