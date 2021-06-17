<?php

namespace Lorisleiva\Actions;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class Util
{
    /**
     * @param string|array $paths
     */
    public static function loadClasses($paths, Closure $callback): void
    {
        if (empty($paths = static::getAbsoluteDirectories($paths))) {
            return;
        }

        foreach ((new Finder)->in($paths)->files() as $file) {
            if (class_exists($className = static::getClassnameFromFile($file))) {
                $callback($className);
            }
        }
    }

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

    /**
     * @param string $trait  The trait to check.
     * @param string|array $abstract  The abstract class or its recursives uses as an array.
     * @return bool
     */
    public static function hasTrait(string $trait, $abstract): bool
    {
        $allTraits = is_array($abstract) ? $abstract : class_uses_recursive($abstract);

        return in_array($trait, $allTraits);
    }

    public static function isAbstract(string $className): bool
    {
        return (new ReflectionClass($className))->isAbstract();
    }

    public static function hasStaticMethod(string $className, string $method): bool
    {
        return method_exists($className, $method)
            && (new ReflectionMethod($className, $method))->isStatic();
    }
}
