<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithCommandSignature;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;

class RunsAsCommandsTest extends TestCase
{
    /** @test */
    public function it_fails_when_required_arguments_are_missing()
    {
        try {
            \Artisan::call('calculate:simple');
            $this->fail('Expected a console runtime exception');
        } catch (RuntimeException $e) {
            $this->assertEquals($e->getMessage(), 'Not enough arguments (missing: "operation, left, right").');
        }
    }

    /** @test */
    public function it_fails_when_too_many_arguments_are_passed()
    {
        try {
            $action = new SimpleCalculatorWithCommandSignature();
            new ArgvInput([
                'command-name',
                'addition',
                '1',
                '2',
                'default',
                'extra-argument'
            ], $action->getInputDefinition());
            $this->fail('Expected a console runtime exception');
        } catch (RuntimeException $e) {
            $this->assertEquals($e->getMessage(), 'Too many arguments, expected arguments "operation" "left" "right" "mode".');
        }
    }

    /** @test */
    public function it_parses_command_line_input_correctly()
    {
        $action = new SimpleCalculatorWithCommandSignature();
        $input = new ArgvInput([
            'command-name',
            'addition',
            '1',
            '2',
        ], $action->getInputDefinition());
        $expected = [
            'operation' => 'addition',
            'left' => 1,
            'right' => 2,
            'mode' => 'default'
        ];
        $this->assertEquals($expected, $action->getAttributesFromCommandInput($input));
    }

    /** @test */
    public function it_writes_expected_output_to_console()
    {
        \Artisan::call('calculate:simple', [
            'operation' => 'addition',
            'left' => '1',
            'right' => '2'
        ]);
        $output = \Artisan::output();
        $this->assertEquals("3\n", $output);
    }

    /** @test */
    public function it_dumps_returned_arrays_to_console()
    {
        $arguments = [
            'operation' => 'addition',
            'left' => '1',
            'right' => '2',
            'mode' => 'return-attributes'
        ];
        \Artisan::call('calculate:simple', $arguments);
        $output = \Artisan::output();
        $expected = <<<'EXPECTED'
array:4 [
  "operation" => "addition"
  "left" => 1
  "right" => 2
  "mode" => "return-attributes"
]

EXPECTED;
        $this->assertEquals($expected, $output);
    }

    /** @test */
    public function it_writes_returned_booleans_as_string()
    {
        $arguments = [
            'operation' => 'addition',
            'left' => '1',
            'right' => '2',
            'mode' => 'return-true'
        ];
        \Artisan::call('calculate:simple', $arguments);
        $output = \Artisan::output();
        $this->assertEquals("true\n", $output);
    }

    /** @test */
    public function it_dumps_returned_objects_to_console()
    {
        $arguments = [
            'operation' => 'addition',
            'left' => '1',
            'right' => '2',
            'mode' => 'return-object'
        ];
        \Artisan::call('calculate:simple', $arguments);
        $output = \Artisan::output();
        $expected = <<<'EXPECTED'
Lorisleiva\Actions\Tests\Actions\SimpleDTO {#4784
  +public: "public-attribute"
  #protected: "protected-attribute"
  -private: "private-attribute"
  +array: array:3 [
    0 => 1
    1 => 2
    2 => 3
  ]
}

EXPECTED;
        $this->assertEquals($expected, $output);
    }
}
