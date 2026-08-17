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

        // StaticCall::getArgs() asserts against being called on a first-class callable.
        if ($call->isFirstClassCallable()) {
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
        // hasMethod() also reports @method, __call and @mixin methods, which
        // getNativeMethod() then throws on.
        if (! $classReflection->hasNativeMethod('handle')) {
            return null;
        }

        return $classReflection->getNativeMethod('handle');
    }

    /**
     * Removes the $boolean condition argument from a runIf()/runUnless() call,
     * so the remaining args line up with handle()'s parameters.
     *
     * @param array<Arg> $args
     * @return array<Arg>
     */
    public function stripConditionArg(array $args, string $methodName): array
    {
        $index = $this->findConditionArgIndex($args, $methodName);

        if ($index === null) {
            return $args;
        }

        unset($args[$index]);

        return array_values($args);
    }

    /**
     * Finds the $boolean condition argument of a runIf()/runUnless() call.
     *
     * @param array<Arg> $args
     */
    public function getConditionArg(array $args, string $methodName): ?Arg
    {
        $index = $this->findConditionArgIndex($args, $methodName);

        return $index === null ? null : $args[$index];
    }

    /** @param array<Arg> $args */
    private function findConditionArgIndex(array $args, string $methodName): ?int
    {
        if (! ($methodName === 'runIf' || $methodName === 'runUnless')) {
            return null;
        }

        if (count($args) === 0) {
            return null;
        }

        if ($args[0]->name === null) {
            return 0;
        }

        foreach ($args as $i => $arg) {
            if ($arg->name !== null && $arg->name->toString() === 'boolean') {
                return $i;
            }
        }

        return null;
    }
}
