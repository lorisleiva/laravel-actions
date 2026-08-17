<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

final class RunThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    public function __construct(
        private ActionHelper $helper,
        private bool $implicitThrows,
    ) {
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->helper->isActionProxyMethod($methodReflection);
    }

    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        $classReflection = $this->helper->resolveActionClass($methodCall, $scope);

        $handleMethod = $classReflection === null
            ? null
            : $this->helper->getHandleMethod($classReflection);

        return $handleMethod?->getThrowType() ?? $this->implicitThrowType();
    }

    /**
     * PHPStan reads a null throw type as "throws nothing", so anything this
     * extension cannot determine has to fall back to what PHPStan would infer
     * for run() on its own.
     */
    private function implicitThrowType(): ?Type
    {
        return $this->implicitThrows ? new ObjectType(Throwable::class) : null;
    }
}
