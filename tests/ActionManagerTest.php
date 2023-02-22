<?php

use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Lorisleiva\Actions\Facades\Actions;
use Lorisleiva\Actions\Tests\Stubs\CustomJobDecorator;
use Lorisleiva\Actions\Tests\Stubs\CustomUniqueJobDecorator;

it('resolves from the container', function () {
    $manager = app(ActionManager::class);

    expect($manager instanceof ActionManager)->toBeTrue();
});

it('resolves as a Facade', function () {
    $manager = Actions::getFacadeRoot();

    expect($manager instanceof ActionManager)->toBeTrue();
});

it('resolves as a singleton', function () {
    $managerA = app(ActionManager::class);
    $managerB = app(ActionManager::class);

    expect($managerA)->toBe($managerB);
});

it('stores the configured job decorator classes', function () {
    expect(ActionManager::$jobDecorator)->toBe(JobDecorator::class);
    expect(ActionManager::$uniqueJobDecorator)->toBe(UniqueJobDecorator::class);
});

it('can register custom job decorator classes', function () {
    ActionManager::useJobDecorator(CustomJobDecorator::class);
    ActionManager::useUniqueJobDecorator(CustomUniqueJobDecorator::class);

    expect(ActionManager::$jobDecorator)->toBe(CustomJobDecorator::class);
    expect(ActionManager::$uniqueJobDecorator)->toBe(CustomUniqueJobDecorator::class);
});
