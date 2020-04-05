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
     * @var string
     */
    public static $cacheKey = 'laravel-actions:discovered';

    /**
     * ActionResolver constructor.
     */
    public function __construct()
    {
        $this->filesystemStrategy = new FilesystemStrategy();
        $this->classnameStrategy = new ClassnameStrategy();
    }

    /**
     * Get all discovered actions as instances
     * @return Collection|Action[]
     */
    public function getActions(): Collection
    {
        if (!config()->get('laravel-actions.discovery.caching.enabled')) {
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
