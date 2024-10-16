<?php

namespace Lorisleiva\Actions\Macros;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\Routing\ActionResourceRegistrar;

class ActionRouteMacros
{
    public function actions(): callable
    {
        return function (string $name, string $namespace = 'App\Actions', array $options = []): PendingResourceRegistration {
            /** @var Router $router */
            $router = $this;
            $registrar = new ActionResourceRegistrar($router);

            return new PendingResourceRegistration(
                $registrar, $name, $namespace, $options
            );
        };
    }
}
