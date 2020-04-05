<?php

namespace Lorisleiva\Actions;

use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\DiscoveryStrategies\ClassnameStrategy;
use Lorisleiva\Actions\DiscoveryStrategies\FilesystemStrategy;

class ActionDiscovery
{
    /**
     * @var FilesystemStrategy
     */
    private $filesystemStrategy;
    /**
     * @var ClassnameStrategy
     */
    private $classnameStrategy;
    /**
     * @var bool
     */
    private $useCache;
    /**
     * @var string
     */
    public static $cacheKey = 'laravel-actions:discovered';

    /**
     * ActionResolver constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->filesystemStrategy = new FilesystemStrategy(Arr::get($config, 'discovery.folders', []));
        $this->classnameStrategy = new ClassnameStrategy(Arr::get($config, 'discovery.classes', []));
        $this->useCache = Arr::get($config, 'discovery.caching.enabled', true);
    }

    /**
     * Get all discovered actions as instances
     * @return Collection|Action[]
     */
    public function getActions(): Collection
    {
        if (!$this->useCache) {
            return $this->discover();
        }
        try {
            return app()->make(CacheManager::class)->rememberForever(self::$cacheKey, function () {
                return $this->discover();
            });
        } catch (BindingResolutionException $e) {
            return $this->discover();
        }
    }

    private function discover(): Collection
    {
        return $this->filesystemStrategy->getActionClasses()
            ->merge($this->classnameStrategy->getActionClasses())
            ->sort()
            ->unique()
            ->map(static function (string $class) {
                try {
                    return app()->make($class);
                } catch (Exception $e) {
                    return null;
                }
            })
            ->filter()
            ->values();
    }
}
