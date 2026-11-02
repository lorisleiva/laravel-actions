<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class CustomUniqueUntilProcessingJobDecorator extends CustomUniqueJobDecorator implements ShouldBeUniqueUntilProcessing
{
}
