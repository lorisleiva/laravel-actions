<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Http\Request;

trait RunsAsController
{
    public function __invoke(Request $request)
    {
        return $this->runAsController($request);
    }

    public function runAsController(Request $request)
    {
        $this->actionRanAs = 'controller';

        $this->fill($this->getAttributesFromRequest($request));

        $result = $this->run();

        return method_exists($this, 'response') ? $this->response($result, $request) : $result;
    }

    public function getAttributesFromRequest(Request $request)
    {
        $route = $request->route();

        return array_merge(
            $route ? $route->parametersWithoutNulls() : [],
            $request->all()
        );
    }

    public function asController()
    {
        return $this->actionRanAs === 'controller';
    }
}