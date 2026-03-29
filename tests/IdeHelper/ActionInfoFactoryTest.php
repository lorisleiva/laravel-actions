<?php

use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\IdeHelper\ActionInfo;
use Lorisleiva\Actions\IdeHelper\ActionInfoFactory;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\BaseAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\NewAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\NotAnAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\TestAction;
use phpDocumentor\Reflection\Php\Class_;

it('creates a correct trait lookup', function () {
    $result = invade(new ActionInfoFactory())->loadFromPath(__DIR__ . '/stubs');

    expect($result)->toBeArray();
    expect($result[BaseAction::class])->toEqual([AsObject::class]);
    expect($result[NewAction::class])->toContain(AsObject::class, AsJob::class);
    expect($result[TestAction::class])->toContain(...ActionInfo::ALL_TRAITS);

    expect(collect($result)->keys()->toArray())->not()->toContain(NotAnAction::class);
});

it('creates correct ActionInfos', function () {
    $ai = getIdeHelperActionInfo(BaseAction::class);

    expect($ai->asObject)->toBeTrue();
    expect($ai->asCommand)->toBeFalse();

    expect($ai->classInfo instanceof Class_)->toBeTrue();
    expect($ai->classInfo)->not()->toBeNull();
});

it('parses the classes correctly', function () {
    $result = invade(new ActionInfoFactory())->loadPhpDocumentorReflectionClassMap(__DIR__ . '/stubs');

    $keys = collect($result)->keys()->toArray();
    expect($keys)->toContain(NotAnAction::class, BaseAction::class, TestAction::class);
});
