<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan\Fixtures;

use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class ThrowingAction
{
    use AsAction;

    /** @throws RuntimeException */
    public function handle(string $input): string
    {
        if ($input === '') {
            throw new RuntimeException('Empty input');
        }

        return $input;
    }
}

class NonThrowingAction
{
    use AsAction;

    public function handle(): int
    {
        return 42;
    }
}

// Should error: missing @throws
function callThrowingWithoutDeclaring(): string
{
    return ThrowingAction::run('test');
}

// Should NOT error: @throws declared
/** @throws RuntimeException */
function callThrowingWithDeclaring(): string
{
    return ThrowingAction::run('test');
}

// Should NOT error: non-throwing action
function callNonThrowing(): int
{
    return NonThrowingAction::run();
}

// Should error: runIf propagates throws
function callThrowingRunIf(): void
{
    ThrowingAction::runIf(true, 'test');
}

// Should error: runUnless propagates throws
function callThrowingRunUnless(): void
{
    ThrowingAction::runUnless(false, 'test');
}

/** A static run() on a class that is not an action keeps its own throw type. */
class NotAnAction
{
    /** @throws RuntimeException */
    public static function run(): int
    {
        throw new RuntimeException('Not an action');
    }
}

// Should error: the extension leaves non-actions alone
function callNonAction(): int
{
    return NotAnAction::run();
}
