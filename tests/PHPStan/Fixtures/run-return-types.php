<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan\Fixtures;

use Illuminate\Support\Fluent;
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

// run() return types
assertType('int', IntAction::run(1, 2));
assertType('void', VoidAction::run('hello'));
assertType('string|null', NullableAction::run());
assertType('int|string', UnionReturnAction::run());
assertType('bool', AsObjectOnlyAction::run('test'));
assertType('int', OptionalParamsAction::run(1, 2));
assertType('int', OptionalParamsAction::run(1, 2, false));

// runIf() return types — union with Fluent
assertType('Illuminate\Support\Fluent|int', IntAction::runIf(true, 1, 2));
assertType('Illuminate\Support\Fluent|void', VoidAction::runIf(true, 'hello'));
assertType('Illuminate\Support\Fluent|string|null', NullableAction::runIf(true));
assertType('Illuminate\Support\Fluent|int|string', UnionReturnAction::runIf(true));

// runUnless() return types — same as runIf
assertType('Illuminate\Support\Fluent|int', IntAction::runUnless(false, 1, 2));
assertType('bool|Illuminate\Support\Fluent', AsObjectOnlyAction::runUnless(false, 'test'));
