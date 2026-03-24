<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan;

use PHPStan\Rules\Exceptions\MissingCheckedExceptionInFunctionThrowsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<MissingCheckedExceptionInFunctionThrowsRule> */
final class RunThrowTypeExtensionTest extends RuleTestCase
{
    private const NS = 'Lorisleiva\Actions\Tests\PHPStan\Fixtures';

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-throw-test.neon'];
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(MissingCheckedExceptionInFunctionThrowsRule::class);
    }

    public function testThrowTypePropagation(): void
    {
        $ns = self::NS;

        $this->analyse([__DIR__ . '/Fixtures/run-throw-types.php'], [
            [
                "Function {$ns}\callThrowingWithoutDeclaring() throws checked exception RuntimeException but it's missing from the PHPDoc @throws tag.",
                38,
            ],
            [
                "Function {$ns}\callThrowingRunIf() throws checked exception RuntimeException but it's missing from the PHPDoc @throws tag.",
                57,
            ],
            [
                "Function {$ns}\callThrowingRunUnless() throws checked exception RuntimeException but it's missing from the PHPDoc @throws tag.",
                63,
            ],
        ]);
    }
}
