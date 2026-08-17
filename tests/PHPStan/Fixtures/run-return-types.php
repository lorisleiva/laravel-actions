<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan\Fixtures;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsObject;

use function PHPStan\Testing\assertType;

class IntAction
{
    use AsAction;

    public function handle(int $a, int $b): int
    {
        return $a + $b;
    }
}

class VoidAction
{
    use AsAction;

    public function handle(string $name): void
    {
    }
}

class NullableAction
{
    use AsAction;

    public function handle(): ?string
    {
        return null;
    }
}

class UnionReturnAction
{
    use AsAction;

    public function handle(): int|string
    {
        return 1;
    }
}

class AsObjectOnlyAction
{
    use AsObject;

    public function handle(string $input): bool
    {
        return $input !== '';
    }
}

class OptionalParamsAction
{
    use AsAction;

    public function handle(int $a, int $b, bool $addition = true): int
    {
        return $addition ? $a + $b : $a - $b;
    }
}

function runReturnTypes(): void
{
    assertType('int', IntAction::run(1, 2));
    assertType('void', VoidAction::run('hello'));
    assertType('string|null', NullableAction::run());
    assertType('int|string', UnionReturnAction::run());
    assertType('bool', AsObjectOnlyAction::run('test'));
    assertType('int', OptionalParamsAction::run(1, 2));
    assertType('int', OptionalParamsAction::run(1, 2, false));
}

/** runIf() narrows to handle()'s return type when the condition is a literal true/false. */
function runIfReturnTypes(): void
{
    assertType('int', IntAction::runIf(true, 1, 2));
    assertType('void', VoidAction::runIf(true, 'hello'));
    assertType('string|null', NullableAction::runIf(true));
    assertType('int|string', UnionReturnAction::runIf(true));
    assertType('Illuminate\Support\Fluent', IntAction::runIf(false, 1, 2));
}

function runUnlessReturnTypes(): void
{
    assertType('int', IntAction::runUnless(false, 1, 2));
    assertType('bool', AsObjectOnlyAction::runUnless(false, 'test'));
    assertType('Illuminate\Support\Fluent', IntAction::runUnless(true, 1, 2));
}

/** Both union with Fluent when the condition is not statically known. */
function withVariableCondition(bool $flag): void
{
    assertType('Illuminate\Support\Fluent|int', IntAction::runIf($flag, 1, 2));
    assertType('Illuminate\Support\Fluent|int', IntAction::runUnless($flag, 1, 2));
}

function viaClassStringVariable(): void
{
    /** @var class-string<IntAction> $actionClass */
    $actionClass = IntAction::class;
    assertType('int', $actionClass::run(1, 2));
}
