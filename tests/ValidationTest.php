<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class ValidationTest extends TestCase
{
    /** @test */
    public function it_uses_validation_rules_to_validate_attributes()
    {
        $attributes = [
            'operation' => 'substraction',
            'left' => 5,
            'right' => 2,
        ];

        $action = new class($attributes) extends SimpleCalculator {
            public function rules() {
                return [
                    'operation' => 'required|in:addition,substraction',
                    'left' => 'required|integer',
                    'right' => 'required|integer',
                ];
            }
        };

        $this->assertTrue($action->passesValidation());
        $this->assertEquals(3, $action->run());
    }

    /** @test */
    public function it_throws_a_validation_exception_when_validator_fails()
    {
        $attributes = [
            'operation' => 'multiplication',
            'left' => 'five',
        ];

        $action = new class($attributes) extends SimpleCalculator {
            public function rules() {
                return [
                    'operation' => 'required|in:addition,substraction',
                    'left' => 'required|integer',
                    'right' => 'required|integer',
                ];
            }
        };

        try {
            $this->assertFalse($action->passesValidation());
            $action->run();
            $this->fail('Expected a ValidationException');
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
        $attributes = [
            'operation' => 'substraction',
            'left' => 5,
            'right' => 10,
        ];

        $action = new class($attributes) extends SimpleCalculator {
            public function withValidator($validator) {
                $validator->after(function ($validator) {
                    if ($this->operation === 'substraction' && $this->left <= $this->right) {
                        $validator->errors()->add('left', 'Left must be greater than right when substracting.');
                    }
                });
            }
        };

        try {
            $this->assertFalse($action->passesValidation());
            $action->run();
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals([
                'left' => ['Left must be greater than right when substracting.'],
            ], $e->errors());
        }
    }
}