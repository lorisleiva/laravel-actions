<?php

namespace Lorisleiva\Skeleton\Facades;

use Illuminate\Support\Facades\Facade;
use Lorisleiva\Skeleton\Skeleton as SkeletonManager;

/**
 * @see \Lorisleiva\Skeleton\Skeleton
 */
class Skeleton extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SkeletonManager::class;
    }
}
