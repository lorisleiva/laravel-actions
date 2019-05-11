<?php

namespace Lorisleiva\Actions\Tests\Actions;

class SimpleCalculatorWithMiddleware extends SimpleCalculator
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->middleware(function ($request, $next) {
            abort(400, 'Intercepted by a middleware');
            return $next($request);
        });
    }
}