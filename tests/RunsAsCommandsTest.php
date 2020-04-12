<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Lorisleiva\Actions\Facades\Actions;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithCommandSignature;
use Symfony\Component\Console\Exception\RuntimeException;

class RunsAsCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Actions::loadAction(SimpleCalculatorWithCommandSignature::class);
    }

    /** @test */
    public function it_can_successfully_run_as_a_command()
    {
        $this->artisan('calculate:simple', [
                'operation' => 'addition',
                'left' => '3',
                'right' => '5',
            ])
            ->assertExitCode(0)
            ->expectsOutput('8');
    }

    /** @test */
    public function it_fails_when_required_arguments_are_missing()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "operation, left, right").');
        $this->artisan('calculate:simple');
    }

    /** @test */
    public function it_fails_when_we_provide_arguments_that_have_not_been_registered_in_the_signature()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "foo" argument does not exist.');
        $this->artisan('calculate:simple', [
            'foo' => 'bar',
        ]);
    }

    /** @test */
    public function it_can_override_the_way_we_get_attribute_from_the_command()
    {
        Actions::loadAction(new class() extends SimpleCalculatorWithCommandSignature
        {
            protected $commandSignature = 'calculate:simple {number}';

            public function getAttributesFromCommand(Command $command): array
            {
                return [
                    'operation' => 'addition',
                    'left' => (int) $command->argument('number'),
                    'right' => (int) $command->argument('number'),
                ];
            }
        });

        $this->artisan('calculate:simple', ['number' => '10'])
            ->expectsOutput('20');
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
        $expected = 'array:4 [
  "operation" => "addition"
  "left" => 1
  "right" => 2
  "mode" => "return-attributes"
]
';
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
        $expected = 'array:4 [
  "public" => "public-attribute"
  "\x00*\x00protected" => "protected-attribute"
  "\x00Lorisleiva\Actions\Tests\Actions\SimpleDTO\x00private" => "private-attribute"
  "array" => array:3 [
    0 => 1
    1 => 2
    2 => 3
  ]
]
';
        $this->assertEquals($expected, $output);
    }
}
