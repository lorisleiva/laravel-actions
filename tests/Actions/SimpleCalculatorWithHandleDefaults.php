<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;

class SimpleCalculatorWithHandleDefaults extends SimpleCalculator
{
    protected $getAttributesFromConstructor = true;

    public function handle($operation, $left = 50, $right = 100)
    {
        return parent::handle($operation, $left, $right);
    }
}
