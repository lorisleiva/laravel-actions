<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;

final class RunThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    public function __construct(
        private ActionHelper $helper,
    ) {
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            ['run', 'runIf', 'runUnless'],
            true,
        );
    }

    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        $classReflection = $this->helper->resolveActionClass($methodCall, $scope);

        if ($classReflection === null) {
            return null;
        }

        $handleMethod = $this->helper->getHandleMethod($classReflection);

        if ($handleMethod === null) {
            return null;
        }

        return $handleMethod->getThrowType();
    }
}
