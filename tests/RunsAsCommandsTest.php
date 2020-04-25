<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Lorisleiva\Actions\Facades\Actions;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithCommandSignature;
use Symfony\Component\Console\Exception\RuntimeException;

class RunsAsCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Actions::register(SimpleCalculatorWithCommandSignature::class);
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
    public function it_fails_when_the_action_is_not_authorized()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');
        $this->artisan('calculate:simple', [
            'operation' => 'unauthorized',
            'left' => '3',
            'right' => '5',
        ]);
    }

    /** @test */
    public function it_fails_when_the_data_is_invalid()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The given data was invalid.');
        $this->artisan('calculate:simple', [
            'operation' => 'invalid',
            'left' => '3',
            'right' => '5',
        ]);
    }

    /** @test */
    public function it_can_override_the_way_we_get_attribute_from_the_command()
    {
        Actions::register(new class() extends SimpleCalculatorWithCommandSignature
        {
            protected static $commandSignature = 'calculate:simple {number}';

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
    public function it_can_override_what_gets_written_to_the_console()
    {
        Actions::register(new class() extends SimpleCalculatorWithCommandSignature
        {
            public function consoleOutput($result, Command $command)
            {
                $command->line("Congratulations! Your result is [$result].");
            }
        });

        $this->artisan('calculate:simple', [
            'operation' => 'addition',
            'left' => '3',
            'right' => '5',
        ])->expectsOutput('Congratulations! Your result is [8].');
    }

    /** @test */
    public function it_can_provide_a_custom_console_exit_code()
    {
        Actions::register(new class() extends SimpleCalculatorWithCommandSignature
        {
            public function consoleOutput($result, Command $command)
            {
                return 42;
            }
        });

        $this->artisan('calculate:simple', [
            'operation' => 'addition',
            'left' => '3',
            'right' => '5',
        ])->assertExitCode(42);
    }

    /** @test */
    public function it_can_request_additional_input_from_the_console()
    {
        Actions::register(new class() extends SimpleCalculatorWithCommandSignature
        {
            public function asCommand(Command $command)
            {
                $this->multiplier = (int) $command->ask('What should we multiple the final result by?');
            }

            public function handle($operation, $left, $right)
            {
                return $this->multiplier * parent::handle($operation, $left, $right);
            }
        });

        $input = [
            'operation' => 'addition',
            'left' => '3',
            'right' => '5',
        ];

        $this->artisan('calculate:simple', $input)
            ->expectsQuestion('What should we multiple the final result by?', 3)
            ->expectsOutput('24');
    }
}
