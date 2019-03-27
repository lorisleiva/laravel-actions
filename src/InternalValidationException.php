<?php

namespace App\Actions;

class InternalValidationException extends \RuntimeException
{
    public $validator;
    public $errorBag;

    public function __construct($validator, $errorBag = 'default')
    {
        $this->validator = $validator;
        $this->errorBag = $errorBag;
        $errors = $this->errors();
        $errors = array_map(function($value) {
            return implode(', ', $value);
        }, $errors);

        parent::__construct('The given data was invalid. ' . implode(', ', $errors));
    }

    public function errors()
    {
        return $this->validator->errors()->messages();
    }
}