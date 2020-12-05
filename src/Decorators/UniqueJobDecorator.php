<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UniqueJobDecorator extends JobDecorator implements ShouldBeUnique
{
    /** @var int */
    public $uniqueFor = 0;

    protected function constructed()
    {
        $this->uniqueFor = $this->fromActionMethodOrProperty('getJobUniqueFor', 'jobUniqueFor', 0);

        parent::constructed();
    }

    public function uniqueId()
    {
        $uniqueId = $this->fromActionMethodOrProperty('getJobUniqueId', 'jobUniqueId', '');
        $prefix = '.' . get_class($this->action);
        $prefix .= $uniqueId ? '.' : '';

        return $prefix . $uniqueId;
    }

    public function uniqueVia()
    {
        if ($this->hasMethod('getJobUniqueVia')) {
            return $this->callMethod('getJobUniqueVia');
        }

        return Container::getInstance()->make(Cache::class);
    }
}
