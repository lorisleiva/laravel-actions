<?php

namespace Lorisleiva\Actions\Tests\Actions;

class SimpleCalculatorWithValidation extends SimpleCalculator
{
    public function authorize()
    {
        return $this->operation !== 'unauthorized';
    }

    public function rules()
    {
        return [
            'operation' => 'required|in:addition,substraction',
            'left' => 'required|integer',
            'right' => 'required|integer',
        ];
    }
}