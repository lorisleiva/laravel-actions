<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Attributes\UniqueFor;

class UniqueJobDecorator extends JobDecorator implements ShouldBeUnique
{
    public int $uniqueFor = 0;

    protected function constructed(): void
    {
        $this->uniqueFor = (int) $this->fromActionMethodPropertyOrAttribute('getJobUniqueFor', 'jobUniqueFor', UniqueFor::class);

        parent::constructed();
    }

    public function uniqueId(): string
    {
        $uniqueId = $this->fromActionWithParameters('getJobUniqueId', 'jobUniqueId', '');
        $prefix = '.' . get_class($this->action);
        $prefix .= $uniqueId ? '.' : '';

        return $prefix . $uniqueId;
    }

    public function uniqueVia()
    {
        return $this->fromActionMethod('getJobUniqueVia', $this->parameters, function () {
            return Container::getInstance()->make(Cache::class);
        });
    }

    protected function fromActionWithParameters(string $method, string $property, $default = null)
    {
        return $this->fromActionMethodOrProperty($method, $property, $default, $this->parameters);
    }
}
