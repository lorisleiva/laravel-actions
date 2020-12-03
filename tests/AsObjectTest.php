<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Filesystem\Filesystem;
use Lorisleiva\Actions\Concerns\AsObject;

class AsObjectTest
{
    use AsObject;

    /** @var Filesystem */
    public $filesystem;

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
