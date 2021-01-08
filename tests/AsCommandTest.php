<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Decorators\CommandDecorator;

class AsCommandTest
{
    use AsCommand;

    public static ?CommandDecorator $decorator;

    public function handle(CommandDecorator $command)
    {
        static::$decorator = $command;

        $result = ($command->hasOption('sub') && $command->option('sub'))
            ? $command->argument('left') - $command->argument('right')
            : $command->argument('left') + $command->argument('right');

        $command->line("Result: {$result}");
    }

    public function getCommandSignature(): string
    {
        return 'my:command {left} {right} {--sub}';
    }

    public function getCommandDescription(): string
    {
        return 'My command description.';
    }

    public function getCommandHelp(): string
    {
        return 'My command help.';
    }

    public function isCommandHidden(): bool
    {
        return true;
    }
}

beforeEach(function () {
    // Given we registered the action as a command.
    registerCommands([AsCommandTest::class]);

    // And reset the command decorator between tests.
    AsCommandTest::$decorator = null;
});

it('can run as a command', function () {
    // When we run the action as a command.
    $command = $this->artisan('my:command 8 5 --sub');

    // Then we get the expected result.
    $command->expectsOutput('Result: 3');
});

it('configures the command based on the method provided', function () {
    // When we run an action as a command.
    $this->artisan('my:command 8 5 --sub');

    // Then the CommandDecorator was configured using
    // the method defined on the decorated action.
    $decorator = AsCommandTest::$decorator;
    expect($decorator->getName())->toBe('my:command');
    expect($decorator->getDescription())->toBe('My command description.');
    expect($decorator->getHelp())->toBe('My command help.');
    expect($decorator->isHidden())->toBeTrue();
    expect($decorator->option('sub'))->toBe(true);
    expect($decorator->argument('left'))->toBe('8');
    expect($decorator->argument('right'))->toBe('5');
});
