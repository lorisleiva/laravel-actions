<?php

use Lorisleiva\Skeleton\Skeleton;

it('resolves from the container', function () {
    $skeleton = app(Skeleton::class);

    expect($skeleton instanceof Skeleton)->toBeTrue();
});

it('resolves as a singleton', function () {
    $skeletonA = app(Skeleton::class);
    $skeletonB = app(Skeleton::class);

    expect($skeletonA)->toBe($skeletonB);
});
