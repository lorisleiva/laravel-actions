<?php

namespace Lorisleiva\Actions;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SplFileInfo;

class Util
{
    /**
     * @param string|array $paths
     * @return array
     */
    public static function getAbsoluteDirectories($paths): array
    {
        return Collection::wrap($paths)
            ->map(function (string $path) {
                return Str::startsWith($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
            })
            ->unique()
            ->filter(function (string $path) {
                return is_dir($path);
            })
            ->values()
            ->toArray();
    }

    public static function getClassnameFromFile(SplFileInfo $file): string
    {
        return static::getClassnameFromRealpath($file->getRealPath());
    }

    public static function getClassnameFromRealpath(string $realpath): string
    {
        return Container::getInstance()->getNamespace() . str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($realpath, realpath(app_path()).DIRECTORY_SEPARATOR)
        );
    }
}
