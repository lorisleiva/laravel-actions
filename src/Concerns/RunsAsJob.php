<?php

namespace Lorisleiva\Actions\Concerns;

trait RunsAsJob
{
    public function runAsJob()
    {
        $this->actionRanAs = 'job';

        return $this->run();
    }

    public function asJob()
    {
        return $this->actionRanAs === 'job';
    }
}