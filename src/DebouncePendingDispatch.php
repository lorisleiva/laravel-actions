<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\DebounceLock;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Bus\PendingDispatch;

class DebouncePendingDispatch extends PendingDispatch
{
    /**
     * Acquire a debounce lock for the job and set its delay.
     *
     * Overrides Laravel's implementation so that both the debounce duration and
     * the (otherwise dropped) maxWait value forwarded by the DebounceJobDecorator
     * are passed to the lock. The unique-vs-debounced conflict is enforced earlier
     * in AsJob::makeJob(), which covers every dispatch path.
     */
    protected function acquireDebounceLock()
    {
        $job = $this->job;

        $debounceFor = $job->debounceFor ?? null;

        if ($debounceFor === null) {
            return;
        }

        $lock = new DebounceLock(Container::getInstance()->make(Cache::class));

        $result = $lock->acquire($job, $debounceFor, $job->maxWait ?? null);

        $job->debounceOwner = $result['owner'];

        if (is_null($job->delay)) {
            $job->delay = $result['maxWaitExceeded'] ? 0 : $debounceFor;
        }
    }
}
