<?php


namespace Lorisleiva\Actions\DiscoveryStrategies;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Action;

class AutoloaderStrategy implements ActionDiscoveryStrategy
{
    /**
     * @var bool
     */
    private $enabled;

    /**
     * AutoloaderDiscovery constructor.
     * @param bool $enabled
     */
    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
    }

    public function getActionClasses(): Collection
    {
        if (!$this->enabled) {
            return collect();
        }
        $namespace = app()->getNamespace();
        $autoloader = require base_path('vendor/autoload.php');
        $classmap = $autoloader->getClassMap();
        return collect($classmap)
            ->keys()
            ->filter(static function ($fqn) use ($namespace) {
                return Str::startsWith($fqn, $namespace) && is_subclass_of($fqn, Action::class);
            });
    }
}
