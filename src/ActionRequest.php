<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\ResolvesRouteDependencies;
use Illuminate\Routing\Route;
use Lorisleiva\Actions\Concerns\ValidateActions;

class ActionRequest extends FormRequest
{
    use ResolvesRouteDependencies;
    use ValidateActions;

    public function validateResolved(): void
    {
        // Cancel the auto-resolution trait.
    }

    public function getDefaultValidationData(): array
    {
        return $this->all();
    }

    /**
     * Resolve the dependencies of the action's authorize method the same way
     * they are resolved on the action's controller method. This means class
     * dependencies are matched against the route parameters by type and
     * any remaining route parameter is provided positionally.
     *
     * @return mixed
     */
    protected function callAuthorizationMethod()
    {
        $route = ($this->getRouteResolver())();

        if (! $route instanceof Route) {
            return $this->resolveAndCallMethod('authorize');
        }

        return $this->callMethod('authorize', array_values(
            $this->resolveClassMethodDependencies(
                $route->parametersWithoutNulls(),
                $this->action,
                'authorize'
            )
        ));
    }
}
