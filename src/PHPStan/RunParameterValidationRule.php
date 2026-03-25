<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<StaticCall>
 */
final class RunParameterValidationRule implements Rule
{
    public function __construct(
        private ActionHelper $helper,
        private RuleLevelHelper $ruleLevelHelper,
    ) {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /** @return list<\PHPStan\Rules\IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof StaticCall);

        $classReflection = $this->helper->resolveActionClass($node, $scope);

        if ($classReflection === null) {
            return [];
        }

        $methodName = $node->name->toString();

        $handleMethod = $this->helper->getHandleMethod($classReflection);

        if ($handleMethod === null) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Call to %s::%s() but class has no handle() method.',
                    $classReflection->getDisplayName(),
                    $methodName,
                ))
                    ->identifier('laravelActions.missingHandle')
                    ->build(),
            ];
        }

        $args = $node->getArgs();
        if ($methodName === 'runIf' || $methodName === 'runUnless') {
            $args = array_slice($args, 1);
        }

        $variant = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $handleMethod->getVariants(),
            $handleMethod->getNamedArgumentsVariants(),
        );

        $parameters = $variant->getParameters();
        $isVariadic = $variant->isVariadic();
        $errors = [];

        $minParams = 0;
        foreach ($parameters as $param) {
            if (! $param->isOptional()) {
                $minParams++;
            }
        }

        $isConditional = $methodName === 'runIf' || $methodName === 'runUnless';
        $maxParams = $isVariadic ? null : count($parameters);
        $argCount = count($args);

        // Report counts relative to the actual call (including the condition argument for runIf/runUnless).
        $reportedArgCount = $isConditional ? $argCount + 1 : $argCount;
        $reportedMin = $isConditional ? $minParams + 1 : $minParams;
        $reportedMax = $maxParams !== null ? ($isConditional ? $maxParams + 1 : $maxParams) : null;

        if ($argCount < $minParams) {
            return [
                RuleErrorBuilder::message(sprintf(
                    '%s::%s() expects %s %d %s, %d given.',
                    $classReflection->getDisplayName(),
                    $methodName,
                    $reportedMin === $reportedMax ? 'exactly' : 'at least',
                    $reportedMin,
                    $reportedMin === 1 ? 'argument' : 'arguments',
                    $reportedArgCount,
                ))
                    ->identifier('laravelActions.tooFewArguments')
                    ->build(),
            ];
        }

        if ($maxParams !== null && $argCount > $maxParams) {
            return [
                RuleErrorBuilder::message(sprintf(
                    '%s::%s() expects %s %d %s, %d given.',
                    $classReflection->getDisplayName(),
                    $methodName,
                    $reportedMin === $reportedMax ? 'exactly' : 'at most',
                    $reportedMax,
                    $reportedMax === 1 ? 'argument' : 'arguments',
                    $reportedArgCount,
                ))
                    ->identifier('laravelActions.tooManyArguments')
                    ->build(),
            ];
        }

        foreach ($args as $i => $arg) {
            if ($arg->name !== null) {
                $paramName = $arg->name->toString();
                $param = null;
                $paramIndex = null;
                foreach ($parameters as $j => $p) {
                    if ($p->getName() === $paramName) {
                        $param = $p;
                        $paramIndex = $j;

                        break;
                    }
                }
            } else {
                $param = $parameters[$i] ?? ($isVariadic ? $parameters[count($parameters) - 1] : null);
                $paramIndex = $i;
            }

            if ($param === null) {
                continue;
            }

            $argType = $scope->getType($arg->value);
            $paramType = $param->getType();

            $accepts = $this->ruleLevelHelper->accepts($paramType, $argType, $scope->isDeclareStrictTypes());

            if (! $accepts->result) {
                $reportedIndex = $isConditional ? ($paramIndex ?? $i) + 2 : ($paramIndex ?? $i) + 1;

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Parameter #%d $%s of %s::%s() expects %s, %s given.',
                    $reportedIndex,
                    $param->getName(),
                    $classReflection->getDisplayName(),
                    $methodName,
                    $paramType->describe(VerbosityLevel::typeOnly()),
                    $argType->describe(VerbosityLevel::typeOnly()),
                ))
                    ->identifier('laravelActions.argumentType')
                    ->build();
            }
        }

        return $errors;
    }
}
