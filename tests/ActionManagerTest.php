<?php

use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Facades\Actions;

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

it('returns the classname frontslash', function () {
    $path = app_path() . '/Actions/CustomAction.php';

    $manager = app(ActionManager::class);

    $class = new ReflectionClass($manager);

    $method = $class->getMethod('getClassnameFromPathname');
    $method->setAccessible(true);

    $className = $method->invokeArgs($manager, [$path]);

    expect($className)->toEqual('App\Actions\CustomAction');
});

it('returns the classname backslash', function () {
    $path = app_path() . '\\Actions\\CustomAction.php';

    $manager = app(ActionManager::class);

    $class = new ReflectionClass($manager);

    $method = $class->getMethod('getClassnameFromPathname');
    $method->setAccessible(true);

    $className = $method->invokeArgs($manager, [$path]);

    expect($className)->toEqual('App\Actions\CustomAction');
});
