<?php

namespace Lorisleiva\Actions\DiscoveryStrategies;

use Illuminate\Support\Collection;

interface ActionDiscoveryStrategy
{
    /**
     * @return Collection|string[]
     */
    public function getActionClasses(): Collection;
}
