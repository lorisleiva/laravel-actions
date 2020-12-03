<?php

use Lorisleiva\Actions\ActionManager;

it('resolves from the container', function () {
    $manager = app(ActionManager::class);

    expect($manager instanceof ActionManager)->toBeTrue();
});

it('resolves as a singleton', function () {
    $managerA = app(ActionManager::class);
    $managerB = app(ActionManager::class);

    expect($managerA)->toBe($managerB);
});
