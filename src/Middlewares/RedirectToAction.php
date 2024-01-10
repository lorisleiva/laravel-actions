<?php

namespace Lorisleiva\Actions\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class RedirectToAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        // Make sure there is a valid action being used.
        $actionString = $route->parameter('actionString');
        if (!$actionString || !$actionClassName = Crypt::decryptString($actionString)) {
            abort(404);
        }

        // Figure out the method that needs to be used.
        if (str_contains($actionClassName, '@')) {
            $parts = explode("@", $actionClassName);
            $actionClassName = $parts[0];
            $method = $parts[1];
        } else {
            $actionClassReflected = new ReflectionClass($actionClassName);

            if ($actionClassReflected->hasMethod('asController')) {
                $method = 'asController';
            } else {
                $method = $actionClassReflected->hasMethod('handle') ? 'handle' : '__invoke';
            }
        }

        // Replace the route action and flush the controller.
        $routeAction = array_merge($route->getAction(), [
            'uses' => "$actionClassName@$method",
            'controller' => "$actionClassName@$method",
        ]);

        $route->setAction($routeAction);
        $route->controller = false;

        return $next($request);
    }
}
