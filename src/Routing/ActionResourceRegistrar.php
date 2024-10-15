<?php

namespace Lorisleiva\Actions\Routing;

use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Str;

class ActionResourceRegistrar extends ResourceRegistrar
{
    protected function getResourceAction($resource, $controller, $method, $options): array
    {
        $action = parent::getResourceAction($resource, $controller, $method, $options);

        $resource = Str::camel($resource);
        $actionName = Str::singular($resource);

        $actionClass = match ($method) {
            'index' => 'Get'.ucfirst($resource),
            'create' => 'Create'.ucfirst($actionName),
            'show' => 'Show'.ucfirst($actionName),
            'edit' => 'Edit'.ucfirst($actionName),
            'store' => 'Store'.ucfirst($actionName),
            'update' => 'Update'.ucfirst($actionName),
            'destroy' => 'Destroy'.ucfirst($actionName),
        };

        // Replaces the Controller@action string with the ActionClass string
        $action['uses'] = str_replace('\\\\', '\\', "{$controller}\\{$actionClass}");

        return $action;
    }
}
