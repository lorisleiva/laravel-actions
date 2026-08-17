<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan;

use Lorisleiva\Actions\PHPStan\RunParameterValidationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<RunParameterValidationRule> */
final class RunParameterValidationRuleTest extends RuleTestCase
{
    private const NS = 'Lorisleiva\Actions\Tests\PHPStan\Fixtures';

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-test.neon'];
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(RunParameterValidationRule::class);
    }

    public function testParameterValidation(): void
    {
        $ns = self::NS;

        $this->analyse([__DIR__ . '/Fixtures/run-parameter-validation.php'], [
            [
                "{$ns}\AddAction::run() expects exactly 2 arguments, 0 given.",
                89,
            ],
            [
                "{$ns}\AddAction::run() expects exactly 2 arguments, 1 given.",
                90,
            ],
            [
                "{$ns}\AddAction::runIf() expects exactly 3 arguments, 1 given.",
                91,
            ],
            [
                "{$ns}\AddAction::runUnless() expects exactly 3 arguments, 2 given.",
                92,
            ],
            [
                "{$ns}\AddAction::run() expects exactly 2 arguments, 3 given.",
                97,
            ],
            [
                "Parameter #1 \$a of {$ns}\AddAction::run() expects int, string given.",
                102,
            ],
            [
                "Parameter #2 \$b of {$ns}\AddAction::run() expects int, string given.",
                103,
            ],
            [
                "Parameter #1 \$a of {$ns}\OptionalAction::run() expects int, string given.",
                104,
            ],
            [
                "Call to {$ns}\NoHandleAction::run() but class has no handle() method.",
                109,
            ],
            [
                "Call to {$ns}\NoHandleAction::runIf() but class has no handle() method.",
                110,
            ],
            // Named argument type errors
            [
                "Parameter #3 \$c of {$ns}\NamedArgAction::run() expects float, string given.",
                123,
            ],
            [
                "Parameter #1 \$a of {$ns}\NamedArgAction::run() expects int, string given.",
                124,
            ],
            [
                "Parameter #2 \$a of {$ns}\AddAction::runIf() expects int, string given.",
                130,
            ],
            [
                "{$ns}\AddAction::runIf() expects exactly 3 arguments, 0 given.",
                139,
            ],
            [
                "{$ns}\AddAction::runUnless() expects exactly 3 arguments, 0 given.",
                140,
            ],
            [
                "{$ns}\NoParamsAction::runIf() expects exactly 1 argument, 2 given.",
                152,
            ],
        ]);
    }
}
