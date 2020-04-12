<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class ActionManager
{
    /** @var array array */
    protected $paths = [];

    public function __construct()
    {
        $this->paths = [
            app_path('Actions'),
        ];
    }

    /**
     * Define the paths to use when loading actions.
     *
     * @param $paths
     * @return $this
     */
    public function paths($paths): ActionManager
    {
        $this->paths = array_unique(Arr::wrap($paths));

        return $this;
    }

    /**
     * Load all actions found in the provided paths.
     */
    public function load(): void
    {
        $paths = array_filter($this->paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        foreach ((new Finder)->in($paths)->files() as $file) {
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
        if (! $this->isAction($action)) {
            return;
        }

        if (is_string($action)) {
            $action = new $action();
        }

        $action->registerCommand();
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
