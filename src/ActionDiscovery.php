<?php

namespace Lorisleiva\Actions;

use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\DiscoveryStrategies\AutoloaderStrategy;
use Lorisleiva\Actions\DiscoveryStrategies\ClassnameStrategy;
use Lorisleiva\Actions\DiscoveryStrategies\FilesystemStrategy;

class ActionDiscovery
{
    /**
     * @var AutoloaderStrategy
     */
    private $autoloaderStrategy;
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
     * @var int
     */
    private $cacheTtl;
    /**
     * @var string
     */
    public static $cacheKey;

    /**
     * ActionResolver constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->autoloaderStrategy = new AutoloaderStrategy(Arr::get($config, 'discovery.autoloader', false));
        $this->filesystemStrategy = new FilesystemStrategy(Arr::get($config, 'discovery.folders', []));
        $this->classnameStrategy = new ClassnameStrategy(Arr::get($config, 'discovery.classes', []));
        $this->useCache = Arr::get($config, 'discovery.caching.enabled', true);
        $this->cacheTtl = Arr::get($config, 'discovery.caching.ttl', -1);
        self::$cacheKey = Arr::get($config, 'discovery.caching.cacheKey', 'laravel-actions:discovered');
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
            $cache = app()->make(CacheManager::class);
            if ($this->cacheTtl === -1) {
                return $cache->rememberForever(self::$cacheKey, function () {
                    return $this->discover();
                });
            }
            return $cache->remember(self::$cacheKey, $this->cacheTtl, function () {
                return $this->discover();
            });
        } catch (BindingResolutionException $e) {
            return $this->discover();
        }
    }

    private function discover(): Collection
    {
        $fromAutoloader = $this->autoloaderStrategy->getActionClasses();
        $fromFilesystem = $this->filesystemStrategy->getActionClasses();
        $fromClassname = $this->classnameStrategy->getActionClasses();
        return $fromAutoloader
            ->merge($fromFilesystem)
            ->merge($fromClassname)
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
