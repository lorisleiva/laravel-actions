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
            'create' => 'ShowCreate'.ucfirst($actionName),
            'show' => 'Show'.ucfirst($actionName),
            'edit' => 'ShowEdit'.ucfirst($actionName),
            'store' => 'Create'.ucfirst($actionName),
            'update' => 'Update'.ucfirst($actionName),
            'destroy' => 'Delete'.ucfirst($actionName),
        };

        // Replaces the Controller@action string with the ActionClass string
        $action['uses'] = str_replace('\\\\', '\\', "{$controller}\\{$actionClass}");

        return $action;
    }
}
