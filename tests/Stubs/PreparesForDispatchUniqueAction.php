<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Queue\ShouldBeUnique;

class PreparesForDispatchUniqueAction extends PreparesForDispatchAction implements ShouldBeUnique
{
    public function getJobUniqueId(int $id = 1): int
    {
        return 1;
    }
}
