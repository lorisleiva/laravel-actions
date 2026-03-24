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
                55,
            ],
            [
                "{$ns}\AddAction::run() expects exactly 2 arguments, 1 given.",
                56,
            ],
            [
                "{$ns}\AddAction::runIf() expects exactly 3 arguments, 1 given.",
                57,
            ],
            [
                "{$ns}\AddAction::runUnless() expects exactly 3 arguments, 2 given.",
                58,
            ],
            [
                "{$ns}\AddAction::run() expects exactly 2 arguments, 3 given.",
                61,
            ],
            [
                "Parameter #1 \$a of {$ns}\AddAction::run() expects int, string given.",
                64,
            ],
            [
                "Parameter #2 \$b of {$ns}\AddAction::run() expects int, string given.",
                65,
            ],
            [
                "Parameter #1 \$a of {$ns}\OptionalAction::run() expects int, string given.",
                66,
            ],
            [
                "Call to {$ns}\NoHandleAction::run() but class has no handle() method.",
                69,
            ],
            [
                "Call to {$ns}\NoHandleAction::runIf() but class has no handle() method.",
                70,
            ],
        ]);
    }
}
