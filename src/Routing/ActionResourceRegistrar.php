<?php

namespace Lorisleiva\Actions\Routing;

use Closure;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Str;

class ActionResourceRegistrar extends ResourceRegistrar
{
    private static ?Closure $actionResolver = null;

    protected function getResourceAction($resource, $controller, $method, $options): array
    {
        $action = parent::getResourceAction($resource, $controller, $method, $options);

        $resource = str_replace('.', '_', $resource);
        $resource = Str::camel($resource);
        $actionName = Str::singular($resource);

        if (static::$actionResolver) {
            $actionClass = call_user_func(static::$actionResolver, $resource, $method);
        }

        if (empty($actionClass)) {
            $actionClass = match ($method) {
                'index' => 'Get'.ucfirst($resource),
                'create' => 'ShowCreate'.ucfirst($actionName),
                'show' => 'Show'.ucfirst($actionName),
                'edit' => 'ShowEdit'.ucfirst($actionName),
                'store' => 'Create'.ucfirst($actionName),
                'update' => 'Update'.ucfirst($actionName),
                'destroy' => 'Delete'.ucfirst($actionName),
            };
        }

        // Replaces the Controller@action string with the ActionClass string
        $action['uses'] = str_replace('\\\\', '\\', "{$controller}\\{$actionClass}");

        return $action;
    }

    /**
     * Use this in your RouteServiceProvider to override the default action classes
     *
     * @example
     *
     * ActionResourceRegistrar::resolveResourcefulActionsUsing(
     *     function ($resource, $method): ?string {
     *         return ucfirst($method)
     *             .ucfirst(Str::camel(str_replace('.', '-', $resource)))
     *             .'Action';
     *     }
     * );
     *
     * @param Closure $resolver
     * @return void
     */
    public static function resolveResourcefulActionsUsing(Closure $resolver): void
    {
        static::$actionResolver = $resolver;
    }
}
