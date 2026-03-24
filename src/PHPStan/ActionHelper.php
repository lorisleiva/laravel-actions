<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

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
            $callerType = $scope->getType($call->class);
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
}
