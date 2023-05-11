<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Crypt;
use ReflectionClass;

class ActionController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __invoke(string $actionString)
    {
        $actionClassName = Crypt::decryptString($actionString);

        if (str_contains($actionClassName, '@')) {
            return app()->call($actionClassName);
        }

        $actionClassReflected = new ReflectionClass($actionClassName);
        if ($actionClassReflected->hasMethod('asController')) {
            $method = 'asController';
        } else {
            $method = $actionClassReflected->hasMethod('handle') ? 'handle' : '__invoke';
        }

        return app()->call("$actionClassName@$method");
    }
}
