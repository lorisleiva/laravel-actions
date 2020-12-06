<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UniqueJobDecorator extends JobDecorator implements ShouldBeUnique
{
    public int $uniqueFor = 0;

    protected function constructed()
    {
        $this->uniqueFor = (int) $this->fromActionWithParameters('getJobUniqueFor', 'jobUniqueFor', 0);

        parent::constructed();
    }

    public function uniqueId()
    {
        $uniqueId = $this->fromActionWithParameters('getJobUniqueId', 'jobUniqueId', '');
        $prefix = '.' . get_class($this->action);
        $prefix .= $uniqueId ? '.' : '';

        return $prefix . $uniqueId;
    }

    public function uniqueVia()
    {
        if ($this->hasMethod('getJobUniqueVia')) {
            return $this->callMethod('getJobUniqueVia', $this->parameters);
        }

        return Container::getInstance()->make(Cache::class);
    }

    protected function fromActionWithParameters(string $method, string $property, $default = null)
    {
        return $this->fromActionMethodOrProperty($method, $property, $default, $this->parameters);
    }
}
