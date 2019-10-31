<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Action;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class ResolvesValidationTest extends TestCase
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
        $this->assertTrue($action->getValidatorInstance()->passes());
        $this->assertEmpty($action->getValidatorInstance()->errors()->all());
        $this->assertFalse($action->getValidationErrors()->any());
        $this->assertEquals(3, $action->run());
    }

    /** @test */
    public function it_can_access_to_the_validated_data_after_validation()
    {
        $attributes = [
            'operation' => 'addition',
            'left' => 5,
            'right' => 2,
        ];

        $action = new class($attributes) extends SimpleCalculator {
            public function rules() {
                return [
                    'left' => 'required|integer',
                    'right' => 'required|integer',
                ];
            }
        };

        $this->assertTrue($action->passesValidation());
        $this->assertCount(2, $action->validated());
        $this->assertEquals(5, $action->validated()['left']);
        $this->assertEquals(2, $action->validated()['right']);
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

    /** @test */
    public function it_can_create_its_own_validator_instance()
    {
        $action = new class(['operation' => 'valid']) extends Action {
            public function validator($factory) {
                return $factory->make($this->all(), ['operation' => 'in:valid']);
            }
        };

        $this->assertTrue($action->passesValidation());
    }

    /** @test */
    public function it_can_validate_data_directly_in_the_handle_method()
    {
        $action = new class(['operation' => 'valid']) extends Action {
            public function handle() {
                $first = $this->validate(['operation' => 'in:valid']);

                try {
                    $second = $this->validate(['operation' => 'not_in:valid']);
                } catch (\Throwable $th) {
                    $second = null;
                }

                return compact('first', 'second');
            }
        };

        $result = $action->run();
        $this->assertEquals(['operation' => 'valid'], $result['first']);
        $this->assertNull($result['second']);
    }

    /** @test */
    public function validation_should_restart_when_running_again()
    {
        $action = new class extends Action {
            public function rules() {
                return ['name' => 'required'];
            }
            public function handle() {
                return $this->validated();
            }
        };

        $this->assertEquals(
            ['name' => 'Alice'], 
            $action->run(['name' => 'Alice'])
        );

        $this->assertEquals(
            ['name' => 'Bob'], 
            $action->run(['name' => 'Bob'])
        );
    }
}