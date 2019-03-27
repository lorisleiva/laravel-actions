<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class ValidationTest extends TestCase
{
    /** @test */
    public function it_uses_validation_rules_to_validate_attributes()
    {
        $action = $this->validateWith([
            'operation' => 'required|in:addition,substraction',
            'left' => 'required|integer',
            'right' => 'required|integer',
        ]);

        $action->fill([
            'operation' => 'substraction',
            'left' => 5,
            'right' => 2,
        ]);

        $this->assertTrue($action->passesValidation());
        $this->assertEquals(3, $action->run());
    }

    /** @test */
    public function it_throws_a_validation_exception_when_validator_fails()
    {
        $action = $this->validateWith([
            'operation' => 'required|in:addition,substraction',
            'left' => 'required|integer',
            'right' => 'required|integer',
        ]);

        $action->fill([
            'operation' => 'multiplication',
            'left' => 'five',
        ]);

        try {
            $this->assertFalse($action->passesValidation());
            $action->run();
            $this->fails('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals([
                'operation' => ['The selected operation is invalid.'],
                'left' => ['The left must be an integer.'],
                'right' => ['The right field is required.'],
            ], $e->errors());
        }
    }

    /** @test */
    public function it_can_define_complex_validation_logic()
    {
        $action = $this->validateWith([], function ($validator) {
            $validator->after(function ($validator) {
                if ($this->operation === 'substraction' && $this->left <= $this->right) {
                    $validator->errors()->add('left', 'Left must be greater than right when substracting.');
                }
            });
        });

        $action->fill([
            'operation' => 'substraction',
            'left' => 5,
            'right' => 10,
        ]);

        try {
            $this->assertFalse($action->passesValidation());
            $action->run();
            $this->fails('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals([
                'left' => ['Left must be greater than right when substracting.'],
            ], $e->errors());
        }
    }

    protected function validateWith($rules, $withValidator = null, ...$arguments)
    {
        return new class($rules, $withValidator, ...$arguments) extends SimpleCalculator {
            public function __construct($rules, $withValidator, ...$arguments)
            {
                parent::__construct(...$arguments);
                $this->rules = $rules;
                $this->withValidator = $withValidator;
            }

            public function rules()
            {
                return $this->rules;
            }

            public function withValidator($validator)
            {
                if (! $this->withValidator) {
                    return;
                }

                return $this->withValidator->bindTo($this)->__invoke($validator);
            }
        };
    }
}