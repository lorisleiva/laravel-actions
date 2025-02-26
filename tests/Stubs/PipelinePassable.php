<?php

namespace Lorisleiva\Actions\Tests\Stubs;

/**
 * Test fixture used in all of the AsPipeline{*} tests.
 */
class PipelinePassable
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
