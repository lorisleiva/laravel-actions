<?php

declare(strict_types=1);

namespace Lorisleiva\Actions\Tests\PHPStan;

use PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<CatchWithUnthrownExceptionRule> */
final class RunImplicitThrowTypeExtensionTest extends RuleTestCase
{
    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-implicit-throw-test.neon'];
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CatchWithUnthrownExceptionRule::class);
    }

    public function testImplicitThrowsAreNotSuppressed(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/run-implicit-throw-types.php'], []);
    }
}
