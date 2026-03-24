<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class RunReturnTypeExtension implements ExpressionTypeResolverExtension
{
    public function __construct(
        private ActionHelper $helper,
    ) {}

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (! $expr instanceof StaticCall) {
            return null;
        }

        $classReflection = $this->helper->resolveActionClass($expr, $scope);

        if ($classReflection === null) {
            return null;
        }

        $handleMethod = $this->helper->getHandleMethod($classReflection);

        if ($handleMethod === null) {
            return null;
        }

        $methodName = $expr->name->toString();

        $args = $expr->getArgs();
        if ($methodName === 'runIf' || $methodName === 'runUnless') {
            $args = array_slice($args, 1);
        }

        $variant = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $handleMethod->getVariants(),
            $handleMethod->getNamedArgumentsVariants(),
        );

        $handleReturnType = $variant->getReturnType();

        if ($methodName === 'run') {
            return $handleReturnType;
        }

        return TypeCombinator::union(
            $handleReturnType,
            new ObjectType('Illuminate\Support\Fluent'),
        );
    }
}
