<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Bus\PendingChain;
use Lorisleiva\Actions\Concerns\AsJob;

class ActionPendingChain extends PendingChain
{
    public function dispatch()
    {
        /** @var $job AsJob */
        if ($this->usesAsJobTrait($job = $this->job)) {
            $this->job = $job::makeJob(...func_get_args());
        }

        return parent::dispatch();
    }

    public function usesAsJobTrait($job)
    {
        return is_string($job)
            && class_exists($job)
            && in_array(AsJob::class, class_uses_recursive($job));
    }
}
