<?php

namespace Lorisleiva\Actions\Tests\Actions;

class SimpleCalculatorWithCommandSignature extends SimpleCalculatorWithValidation
{
    protected $commandSignature = 'calculate:simple {operation : The operation to perform}
                                                    {left : The left operand} 
                                                    {right : The right operand}';

    protected $commandDescription = 'Simple calculator running as a command';
}
