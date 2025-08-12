<?php

namespace Lorisleiva\Actions\Routing;

use Closure;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Str;

class ActionResourceRegistrar extends ResourceRegistrar
{
    /**
     * @var array<string, Closure>
     */
    private static array $actionResolver = [];

    protected function getResourceAction($resource, $controller, $method, $options): array
    {
        $action = parent::getResourceAction($resource, $controller, $method, $options);

        $resource = Str::camel(str_replace('.', '_', $resource));
        $actionName = Str::singular($resource);

        if (! empty(static::$actionResolver[$method])) {
            $actionClass = call_user_func(static::$actionResolver[$method], $resource);
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
     * ActionResourceRegistrar::resolveResourceAction('index', function (string $resource) {
     *     return 'GetAll'.ucfirst($resource); // e.g. GetAllUsers
     * });
     *
     * @param string $method
     * @param Closure $resolver
     * @return void
     */
    public static function resolveResourceAction(string $method, Closure $resolver): void
    {
        static::$actionResolver[$method] = $resolver;
    }
}
