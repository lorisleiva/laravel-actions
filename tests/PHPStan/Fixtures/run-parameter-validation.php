<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan\Fixtures;

use Lorisleiva\Actions\Concerns\AsAction;

class AddAction
{
    use AsAction;

    public function handle(int $a, int $b): int
    {
        return $a + $b;
    }
}

class NoHandleAction
{
    use AsAction;
}

class VariadicAction
{
    use AsAction;

    public function handle(string ...$names): string
    {
        return implode(', ', $names);
    }
}

class OptionalAction
{
    use AsAction;

    public function handle(int $a, int $b = 0): int
    {
        return $a + $b;
    }
}

// Valid calls — no errors expected
AddAction::run(1, 2);
AddAction::runIf(true, 1, 2);
AddAction::runUnless(false, 1, 2);
VariadicAction::run('a', 'b', 'c');
VariadicAction::run();
OptionalAction::run(1);
OptionalAction::run(1, 2);

// Too few arguments
AddAction::run();
AddAction::run(1);
AddAction::runIf(true);
AddAction::runUnless(false, 1);

// Too many arguments
AddAction::run(1, 2, 3);

// Wrong argument types
AddAction::run('not-int', 2);
AddAction::run(1, 'not-int');
OptionalAction::run('not-int');

// No handle method
NoHandleAction::run();
NoHandleAction::runIf(true);

// --- Named arguments ---

class NamedArgAction
{
    use AsAction;

    public function handle(int $a, string $b = 'default', float $c = 0.0): string
    {
        return "$a $b $c";
    }
}

// Valid named argument calls — no errors
NamedArgAction::run(1, c: 3.14);
NamedArgAction::run(a: 1, c: 3.14);
NamedArgAction::run(a: 1, b: 'hello', c: 3.14);
NamedArgAction::runIf(true, 1, c: 3.14);

// Wrong type via named argument — should error
NamedArgAction::run(1, c: 'not-float');
NamedArgAction::run(a: 'not-int');
