<?php

namespace Lorisleiva\Actions\Tests;

/**
 * Test fixture used in all of the AsPipeline{*} tests.
 */
class AsPipelinePassable
{
    public function __construct(public int $count = 0)
    {
        //
    }

    public function increment()
    {
        $this->count++;
    }
}
