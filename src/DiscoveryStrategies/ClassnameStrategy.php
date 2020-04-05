<?php

namespace Lorisleiva\Actions\DiscoveryStrategies;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Action;

class ClassnameStrategy implements ActionDiscoveryStrategy
{
    /**
     * @var array
     */
    private $classes;

    /**
     * ClassnameStrategy constructor.
     * @param array $classes
     */
    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    public function getActionClasses(): Collection
    {
        return collect($this->classes)
            ->filter(static function (string $fqn) {
                return class_exists($fqn) && is_subclass_of($fqn, Action::class);
            })
            ->map(static function (string $fqn) {
                return ltrim($fqn, '\\'); // Remove leading backslash to avoid duplicates
            })
            ->values();
    }
}