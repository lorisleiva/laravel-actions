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

class NamedArgAction
{
    use AsAction;

    public function handle(int $a, string $b = 'default', float $c = 0.0): string
    {
        return "$a $b $c";
    }
}

class NoParamsAction
{
    use AsAction;

    public function handle(): string
    {
        return 'ran';
    }
}

/** @method string handle(string $input) */
class AnnotatedHandleAction
{
    use AsAction;

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): string
    {
        return 'ran';
    }
}

function validCalls(): void
{
    AddAction::run(1, 2);
    AddAction::runIf(true, 1, 2);
    AddAction::runUnless(false, 1, 2);
    VariadicAction::run('a', 'b', 'c');
    VariadicAction::run();
    OptionalAction::run(1);
    OptionalAction::run(1, 2);
}

function tooFewArguments(): void
{
    AddAction::run();
    AddAction::run(1);
    AddAction::runIf(true);
    AddAction::runUnless(false, 1);
}

function tooManyArguments(): void
{
    AddAction::run(1, 2, 3);
}

function wrongArgumentTypes(): void
{
    AddAction::run('not-int', 2);
    AddAction::run(1, 'not-int');
    OptionalAction::run('not-int');
}

function noHandleMethod(): void
{
    NoHandleAction::run();
    NoHandleAction::runIf(true);
}

function validNamedArguments(): void
{
    NamedArgAction::run(1, c: 3.14);
    NamedArgAction::run(a: 1, c: 3.14);
    NamedArgAction::run(a: 1, b: 'hello', c: 3.14);
    NamedArgAction::runIf(true, 1, c: 3.14);
}

function wrongNamedArgumentTypes(): void
{
    NamedArgAction::run(1, c: 'not-float');
    NamedArgAction::run(a: 'not-int');
}

function unknownNamedArguments(): void
{
    NamedArgAction::run(a: 1, typo: 2);
    NamedArgAction::runIf(true, a: 1, typo: 2);

    // A variadic handle() accepts any name.
    VariadicAction::run(anything: 'a');
}

function namedConditionArgument(): void
{
    AddAction::runIf(a: 1, b: 2, boolean: true);
    AddAction::runIf(a: 'not-int', b: 2, boolean: true);
}

/**
 * Zero-arg conditional calls are always fatal at runtime, so they are reported
 * with the full arity of the call, including the condition argument.
 */
function zeroArgumentConditional(): void
{
    AddAction::runIf();
    AddAction::runUnless();
}

function conditionalWithoutParameters(): void
{
    NoParamsAction::runIf(true);
    NoParamsAction::runUnless(false);

    // This rule stays silent on zero-arg calls, PHPStan's native check reports the missing $boolean.
    NoParamsAction::runIf();
    NoParamsAction::runUnless();

    NoParamsAction::runIf(true, 'extra');
}

/** A variadic handle() called conditionally accepts any number of arguments. */
function variadicConditional(): void
{
    VariadicAction::runIf(true);
    VariadicAction::runIf(true, 'a', 'b');
}

function firstClassCallables(): void
{
    $run = AddAction::run(...);
    $runIf = AddAction::runIf(...);
    $runUnless = AddAction::runUnless(...);
    $run(1, 2);
    $runIf(true, 1, 2);
    $runUnless(false, 1, 2);
}

function annotatedHandle(): void
{
    AnnotatedHandleAction::run('hello');
    AnnotatedHandleAction::run();
    AnnotatedHandleAction::runIf(true, 'hello');
}

/**
 * @param array{int, int} $pair
 * @param array{bool, int, int} $all
 */
function unpackedArguments(array $pair, array $all): void
{
    AddAction::run(...$pair);
    AddAction::runIf(true, ...$pair);
    AddAction::runUnless(false, ...$pair);
    AddAction::runIf(...$all);
    NoHandleAction::run(...$pair);
}

abstract class BaseAction
{
    use AsAction;

    public static function twice(): void
    {
        static::run();
        static::run();
    }
}

final class ConcreteAction extends BaseAction
{
    public function handle(): int
    {
        return 1;
    }
}
