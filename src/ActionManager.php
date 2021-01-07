<?php

namespace Lorisleiva\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\DesignPatterns\DesignPattern;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

class ActionManager
{
    /** @var Application */
    protected Application $app;

    /** @var DesignPattern[] */
    protected array $designPatterns = [];

    /** @var bool[] */
    protected array $extended = [];

    public function __construct(Application $app, array $designPatterns = [])
    {
        $this->app = $app;
        $this->setDesignPatterns($designPatterns);
    }

    public function setDesignPatterns(array $designPatterns): ActionManager
    {
        $this->designPatterns = $designPatterns;

        return $this;
    }

    public function getDesignPatterns(): array
    {
        return $this->designPatterns;
    }

    public function getDesignPatternsMatching(array $usedTraits): array
    {
        $filter = function (DesignPattern $designPattern) use ($usedTraits) {
            return in_array($designPattern->getTrait(), $usedTraits);
        };

        return array_filter($this->getDesignPatterns(), $filter);
    }

    public function extend(string $abstract): void
    {
        if ($this->isExtending($abstract)) {
            return;
        }

        if (! $this->shouldExtend($abstract)) {
            return;
        }

        $this->app->extend($abstract, function ($instance) {
            return $this->identifyAndDecorate($instance);
        });

        $this->extended[$abstract] = true;
    }

    public function isExtending(string $abstract): bool
    {
        return isset($this->extended[$abstract]);
    }

    public function shouldExtend(string $abstract): bool
    {
        $usedTraits = class_uses_recursive($abstract);

        return ! empty($this->getDesignPatternsMatching($usedTraits))
            || in_array(AsFake::class, $usedTraits);
    }

    public function identifyAndDecorate($instance, $limit = 10)
    {
        $usedTraits = class_uses_recursive($instance);

        if (in_array(AsFake::class, $usedTraits) && $instance::isFake()) {
            $instance = $instance::mock();
        }

        if (! $designPattern = $this->identifyFromBacktrace($usedTraits, $limit, $frame)) {
            return $instance;
        }

        return $designPattern->decorate($instance, $frame);
    }

    public function identifyFromBacktrace($usedTraits, $limit = 10, BacktraceFrame &$frame = null): ?DesignPattern
    {
        $designPatterns = $this->getDesignPatternsMatching($usedTraits);
        $backtraceOptions = DEBUG_BACKTRACE_PROVIDE_OBJECT
            | DEBUG_BACKTRACE_IGNORE_ARGS;

        foreach (debug_backtrace($backtraceOptions, $limit) as $frame) {
            $frame = new BacktraceFrame($frame);

            /** @var DesignPattern $designPattern */
            foreach ($designPatterns as $designPattern) {
                if ($designPattern->recognizeFrame($frame)) {
                    return $designPattern;
                }
            }
        }

        return null;
    }

    public function registerRoutes($paths = 'app/Actions'): void
    {
        $paths = Collection::wrap($paths)
            ->map(function (string $path) {
                return Str::startsWith($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
            })
            ->unique()
            ->filter(function (string $path) {
                return is_dir($path);
            })
            ->values();

        if ($paths->isEmpty()) {
            return;
        }

        foreach ((new Finder)->in($paths->toArray())->files() as $file) {
            $this->registerRoutesForAction(
                $this->getClassnameFromPathname($file->getPathname())
            );
        }
    }

    public function registerRoutesForAction(string $className): void
    {
        if (! in_array(AsController::class, class_uses_recursive($className))) {
            return;
        }

        if (! method_exists($className, 'routes')) {
            return;
        }

        if (! (new ReflectionMethod($className, 'routes'))->isStatic()) {
            return;
        }

        $className::routes($this->app->make(Router::class));
    }

    protected function getClassnameFromPathname(string $pathname): string
    {
        return $this->app->getNamespace() . str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($pathname, realpath(app_path()).DIRECTORY_SEPARATOR)
        );
    }
}
