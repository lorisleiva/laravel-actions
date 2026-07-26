<?php

namespace Lorisleiva\Actions\Tests\Stubs;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class OperationRequestedAfterCommitEvent implements ShouldDispatchAfterCommit
{
    public string $operation;
    public int $left;
    public int $right;

    public function __construct(string $operation, int $left, int $right)
    {
        $this->operation = $operation;
        $this->left = $left;
        $this->right = $right;
    }
}
