<?php

namespace Lorisleiva\Actions\Concerns;

trait RunsAsJob
{
    public function runAsJob()
    {
        return $this->run();
    }
}