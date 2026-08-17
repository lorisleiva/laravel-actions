<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan\Fixtures;

use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class ImplicitThrowAction
{
    use AsAction;

    public function handle(): int
    {
        throw new RuntimeException('No @throws tag');
    }
}

class ImplicitThrowNonAction
{
    public static function run(): int
    {
        throw new RuntimeException('Not an action');
    }
}

// None of these catches are dead: run() throws whatever handle() throws.
function catchImplicitAction(): void
{
    try {
        ImplicitThrowAction::run();
    } catch (RuntimeException $e) {
    }
}

function catchImplicitActionRunIf(bool $flag): void
{
    try {
        ImplicitThrowAction::runIf($flag);
    } catch (RuntimeException $e) {
    }
}

function catchImplicitNonAction(): void
{
    try {
        ImplicitThrowNonAction::run();
    } catch (RuntimeException $e) {
    }
}
