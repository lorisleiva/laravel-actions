<?php

namespace Lorisleiva\Actions;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

abstract class Action extends Controller
{
    use Concerns\DependencyResolver;
    use Concerns\HasAttributes;
    use Concerns\ValidatesAttributes;

    protected $internalValidation = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function __invoke(Request $request)
    {
        return $this->runAsController($request);
    }

    public function runAsController(Request $request)
    {
        $this->fill($this->getAttributesFromRequest($request));

        return $this->response($this->run(true), $request);
    }

    public function runAsListener($event)
    {
        $this->fill($this->getAttributesFromEvent($event));

        return $this->run();
    }

    public function run($http = false)
    {
        if ($this->internalValidation || $http) {
            $this->validate($http);
        }

        $parameters = $this->resolveMethodDependencies($this, 'handle');

        return $this->handle(...$parameters);
    }

    public function getAttributesFromRequest(Request $request)
    {
        return array_merge(
            $request->route()->parametersWithoutNulls(),
            $request->all()
        );
    }

    public function getAttributesFromEvent($event)
    {
        return [];
    }

    public function response($result, Request $request)
    {
        return $result;
    }
}