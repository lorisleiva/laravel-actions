<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Illuminate\Routing\Router;

class SimpleCalculatorWithRoutes extends SimpleCalculator
{
    public static function routes(Router $router)
    {
        $router->get('/calculator-with-routes/{operation}/{left}/{right}', static::class);
    }
}
