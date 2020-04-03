<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Symfony\Component\Console\Input\InputInterface;

class SimpleCalculatorWithCommandSignature extends SimpleCalculatorWithValidation
{
    protected $commandSignature = 'calculate:simple {operation : The operation to perform}
                                                    {left : The left operand} 
                                                    {right : The right operand}
                                                    {mode=default : Test mode}';

    protected $commandDescription = 'Simple calculator running as a command';

    public function getAttributesFromCommandInput(InputInterface $input): array
    {
        return [
            'operation' => $input->getArgument('operation'),
            'left' => (int) $input->getArgument('left'),
            'right' => (int) $input->getArgument('right'),
            'mode' => $input->getArgument('mode')
        ];
    }

    public function handle($operation, $left, $right)
    {
        switch ($this->get('mode')) {
            case 'return-attributes':
                return $this->all();
            case 'return-true':
                return true;
            case 'return-object':
                return new SimpleDTO();
            default:
                return parent::handle($operation, $left, $right);
        }
    }
}

class SimpleDTO {
    public $public = 'public-attribute';
    protected $protected = 'protected-attribute';
    private $private = 'private-attribute';
    public $array = [1,2,3];
}
