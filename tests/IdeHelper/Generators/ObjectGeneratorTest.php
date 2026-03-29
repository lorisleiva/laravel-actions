<?php

use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsObjectGenerator;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\Custom\Method;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\BaseAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\DefaultParameterValuesAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\UnionTypeAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\VoidAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\VoidActionWithNoReturnType;

it('can render the run method', function (string $class, string $docblockExpectation) {
    $ai = getIdeHelperActionInfo($class);

    /** @var \phpDocumentor\Reflection\DocBlock\Tag $docblock */
    $docblock = collect((new AsObjectGenerator())->generate($ai))->first();
    expect($docblock)->toBeInstanceOf(Method::class);

    expect($docblock->render())->toEqual($docblockExpectation);
})->with([
    "BaseAction" => [BaseAction::class, '@method static string run()'],
    "UnionTypeAction" => [UnionTypeAction::class, '@method static int|string run(string $string, float|int $number)'],
    "VoidAction" => [VoidAction::class, '@method static void run(int $i)'],
    "VoidActionWithNoReturnType" => [VoidActionWithNoReturnType::class, '@method static mixed run()'],
]);

it('can render the run method with default parameter values', function () {
    $ai = getIdeHelperActionInfo(DefaultParameterValuesAction::class);

    /** @var \phpDocumentor\Reflection\DocBlock\Tag $docblock */
    $docblock = collect((new AsObjectGenerator())->generate($ai))->first();
    expect($docblock)->toBeInstanceOf(Method::class);

    $docblockExpectation = '@method static int run(string $s, bool $var = false)';
    expect($docblock->render())->toEqual($docblockExpectation);
});
