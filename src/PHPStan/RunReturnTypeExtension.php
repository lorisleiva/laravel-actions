<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class RunReturnTypeExtension implements ExpressionTypeResolverExtension
{
    public function __construct(
        private ActionHelper $helper,
    ) {
    }

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

        $conditionArg = $this->helper->getConditionArg($expr->getArgs(), $methodName);
        $args = $this->helper->stripConditionArg($expr->getArgs(), $methodName);

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

        $fluentType = new ObjectType('Illuminate\Support\Fluent');

        if ($conditionArg !== null) {
            $conditionType = $scope->getType($conditionArg->value);
            $runsHandle = $methodName === 'runIf' ? $conditionType->isTrue() : $conditionType->isFalse();
            $skipsHandle = $methodName === 'runIf' ? $conditionType->isFalse() : $conditionType->isTrue();

            if ($runsHandle->yes()) {
                return $handleReturnType;
            }

            if ($skipsHandle->yes()) {
                return $fluentType;
            }
        }

        // void cannot take part in a union, and a void handle() leaves the call
        // evaluating to null.
        $valueType = $handleReturnType->isVoid()->yes()
            ? new NullType()
            : $handleReturnType;

        return TypeCombinator::union($valueType, $fluentType);
    }
}
