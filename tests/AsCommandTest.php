<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;

class AsCommandTest
{
    use AsCommand;

    public function handle(Command $command)
    {
        $result = ($command->hasOption('sub') && $command->option('sub'))
            ? $command->argument('left') - $command->argument('right')
            : $command->argument('left') + $command->argument('right');

        $command->line("Result: {$result}");
    }

    public function getCommandSignature(): string
    {
        return 'my:command {left} {right} {--sub}';
    }

    public function getCommandName(): string
    {
        return 'My command name';
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
        return false;
    }
}

beforeEach(function () {
    // Given we registered the action as a command.
    registerCommands([AsCommandTest::class]);
});

it('can run as a command', function () {
    // When we run the action as a command.
    $command = $this->artisan('my:command 8 5 --sub');

    // Then we get the expected result.
    $command->expectsOutput('Result: 3');
});
