<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;

final class ActionHelper
{
    private const AS_OBJECT_TRAIT = 'Lorisleiva\Actions\Concerns\AsObject';

    private const PROXY_METHODS = ['run', 'runIf', 'runUnless'];

    public function resolveActionClass(StaticCall $call, Scope $scope): ?ClassReflection
    {
        if (! $call->name instanceof Identifier) {
            return null;
        }

        if (! in_array($call->name->toString(), self::PROXY_METHODS, true)) {
            return null;
        }

        if ($call->class instanceof Name) {
            $callerType = $scope->resolveTypeByName($call->class);
        } else {
            $callerType = $scope->getType($call->class)->getObjectTypeOrClassStringObjectType();
        }

        $classReflections = $callerType->getObjectClassReflections();

        if (count($classReflections) !== 1) {
            return null;
        }

        $classReflection = $classReflections[0];

        if (! $classReflection->hasTraitUse(self::AS_OBJECT_TRAIT)) {
            return null;
        }

        return $classReflection;
    }

    public function getHandleMethod(ClassReflection $classReflection): ?ExtendedMethodReflection
    {
        if (! $classReflection->hasMethod('handle')) {
            return null;
        }

        return $classReflection->getNativeMethod('handle');
    }

    /**
     * Removes the leading $boolean condition argument from a runIf()/runUnless() call,
     * so the remaining args line up with handle()'s parameters.
     *
     * @param array<Arg> $args
     * @return array<Arg>
     */
    public function stripConditionArg(array $args, string $methodName): array
    {
        if (! ($methodName === 'runIf' || $methodName === 'runUnless')) {
            return $args;
        }

        if (count($args) === 0) {
            return $args;
        }

        if ($args[0]->name === null) {
            return array_slice($args, 1);
        }

        foreach ($args as $i => $arg) {
            if ($arg->name !== null && $arg->name->toString() === 'boolean') {
                unset($args[$i]);

                return array_values($args);
            }
        }

        return $args;
    }
}
