<?php

use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\DocBlockGeneratorBase;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\EmptyAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\Jobs\WithDecoratorAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\Jobs\WithoutDecoratorAction;

it('cannot find a method that is not there', function () {
    $ai = getIdeHelperActionInfo(EmptyAction::class);
    $method = invade(new DocBlockGeneratorBase())->findMethod($ai, 'handle');
    expect($method)->toBeNull();
});

it('can find a method in the correct precedence', function () {
    $ai = getIdeHelperActionInfo(WithDecoratorAction::class);
    /** @var \phpDocumentor\Reflection\Php\Method $method */
    $method = invade(new DocBlockGeneratorBase())->findMethod($ai, 'asJob', 'handle');
    expect($method->getName())->toBe('asJob');
});

it('can find a method in the correct precedence even when one is not present', function () {
    $ai = getIdeHelperActionInfo(WithoutDecoratorAction::class);
    /** @var \phpDocumentor\Reflection\Php\Method $method */
    $method = invade(new DocBlockGeneratorBase())->findMethod($ai, 'asJob', 'handle');
    expect($method->getName())->toBe('handle');
});
