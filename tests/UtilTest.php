<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Util;

it('parses relative paths into absolute directories that exists', function () {
    // When we request the absolute directories from:
    // - one relative directory path,
    // - one directory that does not exists,
    // - and one file.
    $result = Util::getAbsoluteDirectories([
        'app/Models', 'app/IDontExists', 'composer.json'
    ]);

    // Then we only receive the absolute path of the first directory.
    expect($result)->toBe([
        base_path('app/Models'),
    ]);
});

it('parses a realpath into a class name', function () {
    // Given the realpath of a file in the app directory.
    $realpath = realpath(app_path()) . DIRECTORY_SEPARATOR . 'MyClass.php';

    // When we parse its classname.
    $className = Util::getClassnameFromRealpath($realpath);

    // Then we get the expected namespaced result.
    expect($className)->toBe('App\MyClass');
});
