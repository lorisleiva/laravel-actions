<?php

namespace Lorisleiva\Actions\Concerns;

trait RunsAsJob
{
    public function runAsJob()
    {
        $this->runningAs = 'job';

        return $this->run();
    }
}