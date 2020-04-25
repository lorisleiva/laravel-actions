<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class ActionManager
{
    /** @var Collection */
    protected $paths;

    /** @var Collection */
    protected $loadedActions;

    /**
     * Define the default path to load.
     */
    public function __construct()
    {
        $this->paths('app/Actions');
        $this->loadedActions = collect();
    }

    /**
     * Define the paths to use when loading actions.
     *
     * @param array|string $paths
     * @return $this
     */
    public function paths($paths): ActionManager
    {
        $this->paths = Collection::wrap($paths)
            ->map(function (string $path) {
                return Str::startsWith($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
            })
            ->unique()
            ->filter(function (string $path) {
                return is_dir($path);
            })
            ->values();

        return $this;
    }

    /**
     * Load all actions found in the provided paths.
     */
    public function load(): void
    {
        if ($this->paths->isEmpty()) {
            return;
        }

        foreach ((new Finder)->in($this->paths->toArray())->files() as $file) {
            $this->loadAction(
                $this->getClassnameFromPathname($file->getPathname())
            );
        }
    }

    /**
     * Load one action either through an object or its classname.
     *
     * @param Action|string $action
     * @throws ReflectionException
     */
    public function loadAction($action): void
    {
        if (! $this->isAction($action) || $this->isLoaded($action)) {
            return;
        }

        if (is_string($action)) {
            $action = new $action();
        }

        $action->registerCommand();
        $this->loadedActions->push(get_class($action));
    }

    /**
     * Determine if an object or its classname is an Action.
     *
     * @param mixed $action
     * @return bool
     * @throws ReflectionException
     */
    public function isAction($action): bool
    {
        return is_subclass_of($action, Action::class) &&
            ! (new ReflectionClass($action))->isAbstract();
    }

    /**
     * Determine if an action has already been loaded.
     *
     * @param mixed $action
     * @return bool
     */
    public function isLoaded($action): bool
    {
        $class = is_string($action) ? $action : get_class($action);

        return $this->loadedActions->contains($class);
    }

    /**
     * Get the fully-qualified name of a class from its pathname.
     *
     * @param string $pathname
     * @return string
     */
    protected function getClassnameFromPathname(string $pathname): string
    {
        return App::getNamespace() . str_replace(
            ['/', '.php'],
            ['\\', ''],
            Str::after($pathname, realpath(app_path()).DIRECTORY_SEPARATOR)
        );
    }
}
