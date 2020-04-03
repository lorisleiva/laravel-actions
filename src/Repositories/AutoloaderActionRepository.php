<?php


namespace Lorisleiva\Actions\Repositories;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Action;

class AutoloaderActionRepository implements ActionRepository
{
    /**
     * @var string
     */
    private $cacheKey = 'lorisleiva-actions:list';

    public function all(): array
    {
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
