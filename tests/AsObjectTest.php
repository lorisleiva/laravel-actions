<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Fluent;
use Lorisleiva\Actions\Concerns\AsObject;

class AsObjectTest
{
    use AsObject;

    public Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle(int $left, int $right): int
    {
        return $left + $right;
    }
}

it('provides a static make method that resolves from the container', function () {
    // When we make an object.
    $object = AsObjectTest::make();

    // Then we resolved it from the container.
    expect($object)->toBeInstanceOf(AsObjectTest::class);
    expect($object->filesystem)->toBeInstanceOf(Filesystem::class);
});

it('provides a static run method', function () {
    // When we run an action.
    $result = AsObjectTest::run(1, 2);

    // Then it resolves from the container and delegate to the handle method.
    expect($result)->toBe(3);
});

it('can be run conditionally', function () {
    $result = AsObjectTest::runIf(true, 1, 2);
    expect($result)->toBe(3);

    $result = AsObjectTest::runIf(false, 1, 2);
    expect($result)->toBeInstanceOf(Fluent::class);

    $result = AsObjectTest::runUnless(true, 1, 2);
    expect($result)->toBeInstanceOf(Fluent::class);

    $result = AsObjectTest::runUnless(false, 1, 2);
    expect($result)->toBe(3);
});
