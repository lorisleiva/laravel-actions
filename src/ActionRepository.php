<?php


namespace Lorisleiva\Actions;

use Illuminate\Support\Str;

class ActionRepository
{
    /**
     * @var string
     */
    private $cacheKey = 'lorisleiva-actions:list';

    public function all(bool $refresh = false): array
    {
        if ($refresh) {
            $this->flushCache();
        }
        return \Cache::rememberForever($this->cacheKey, function () {
            return $this->discoverFromAutoloader();
        });
    }

    public function flushCache()
    {
        return \Cache::forget($this->cacheKey);
    }

    private function discoverFromAutoloader(): array
    {
        $namespace = app()->getNamespace();
        $autoloader = require base_path('vendor/autoload.php');
        $classmap = $autoloader->getClassMap();
        return collect($classmap)
            ->keys()
            ->filter(static function ($fqn) use ($namespace) {
                return Str::startsWith($fqn, $namespace) && is_subclass_of($fqn, Action::class);
            })
            ->toArray();
    }

}
