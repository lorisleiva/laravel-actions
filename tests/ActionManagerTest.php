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
