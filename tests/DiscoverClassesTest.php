<?php

use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Tests\Fixtures\Discovery\AbstractAction;
use Lorisleiva\Actions\Tests\Fixtures\Discovery\ConcreteAction;

it('discovers classes from a directory', function () {
    $manager = app(ActionManager::class);
    $method = new ReflectionMethod($manager, 'discoverClasses');

    $classes = $method->invoke($manager, __DIR__ . '/Fixtures/Discovery');

    expect($classes)->toContain(ConcreteAction::class);
    expect($classes)->toContain(AbstractAction::class);
    expect($classes)->not->toContain('Lorisleiva\Actions\Tests\Fixtures\Discovery\NotAClass');
});

it('returns empty collection for non-existent path', function () {
    $manager = app(ActionManager::class);
    $method = new ReflectionMethod($manager, 'discoverClasses');

    $classes = $method->invoke($manager, '/non/existent/path');

    expect($classes)->toBeEmpty();
});

it('skips files without class definitions', function () {
    $manager = app(ActionManager::class);
    $method = new ReflectionMethod($manager, 'discoverClasses');

    $classes = $method->invoke($manager, __DIR__ . '/Fixtures/Discovery');

    $classNames = $classes->map(fn (string $class) => class_basename($class))->toArray();
    expect($classNames)->not->toContain('NotAClass');
});
