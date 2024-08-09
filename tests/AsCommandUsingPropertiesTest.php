<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Decorators\CommandDecorator;

class AsCommandUsingPropertiesTest
{
    use AsCommand;

    public static ?CommandDecorator $decorator;

    public string $commandSignature = 'my:command {left} {right} {--sub}';
    public string $commandDescription = 'My command description.';
    public string $commandHelp = 'My command help.';
    public bool $commandHidden = true;

    public function handle(CommandDecorator $command): void
    {
        static::$decorator = $command;

        $result = ($command->hasOption('sub') && $command->option('sub'))
            ? $command->argument('left') - $command->argument('right')
            : $command->argument('left') + $command->argument('right');

        $command->line("Result: {$result}");
    }
}

beforeEach(function () {
    // Given we registered the action as a command.
    registerCommands([AsCommandUsingPropertiesTest::class]);

    // And reset the command decorator between tests.
    AsCommandUsingPropertiesTest::$decorator = null;
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
    $decorator = AsCommandUsingPropertiesTest::$decorator;
    expect($decorator->getName())->toBe('my:command');
    expect($decorator->getDescription())->toBe('My command description.');
    expect($decorator->getHelp())->toBe('My command help.');
    expect($decorator->isHidden())->toBeTrue();
    expect($decorator->option('sub'))->toBe(true);
    expect($decorator->argument('left'))->toBe('8');
    expect($decorator->argument('right'))->toBe('5');
});
