<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Decorators\CommandDecorator;

class AsCommandAndObjectTest
{
    use AsObject;
    use AsCommand;

    public static ?int $latestResult;

    public string $commandSignature = 'my:command {left} {right} {--sub}';

    public function handle(int $left, int $right, bool $addition = true): int
    {
        return $addition ? $left + $right : $left - $right;
    }

    public function asCommand(CommandDecorator $command): void
    {
        static::$latestResult = $this->handle(
            $command->argument('left'),
            $command->argument('right'),
            ! $command->option('sub'),
        );

        $command->line('Result: ' . static::$latestResult);
    }
}

beforeEach(function () {
    // Given we reset the latest result between tests.
    AsCommandAndObjectTest::$latestResult = null;
});

it('works as an object', function () {
    // When we run the action as an object.
    $result = AsCommandAndObjectTest::run(8, 5, false);

    // Then we get the expected result.
    expect($result)->toBe(3);

    // And the `asListener` method was not called.
    expect(AsCommandAndObjectTest::$latestResult)->toBeNull();
});

it('works as a command', function () {
    // Given we registered the action as a command.
    registerCommands([AsCommandAndObjectTest::class]);

    // When we run the action as a command.
    $command = $this->artisan('my:command 8 5 --sub');

    // Then we get the expected output.
    $command->expectsOutput('Result: 3');

    // And the expected result.
    $command->run();
    expect(AsCommandAndObjectTest::$latestResult)->toBe(3);
});
