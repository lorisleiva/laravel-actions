<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;

class SimpleCalculator extends Action
{
    public function handle($operation, $left, $right)
    {
        switch ($operation) {
            case 'addition':
                return $left + $right;

            case 'substraction':
                return $left - $right;
            
            default:
                throw new \Exception("Operation [$operation] not supported.");
        }
    }

    public function response($result)
    {
        return "($this->operation)\nLeft: $this->left\nRight: $this->right\nResult: $result";
    }
}