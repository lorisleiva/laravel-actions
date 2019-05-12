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
        $this->runningAs = 'controller';

        $this->reset($request->user());
        $this->fill($this->getAttributesFromRequest($request));

        $result = $this->run();

        if (method_exists($this, 'response')) {
            return $this->response($result, $request);
        }

        if (method_exists($this, 'jsonResponse') && $request->wantsJson()) {
            return $this->jsonResponse($result, $request);
        }

        if (method_exists($this, 'htmlResponse') && ! $request->wantsJson()) {
            return $this->htmlResponse($result, $request);
        }

        return $result;
    }

    public function getAttributesFromRequest(Request $request)
    {
        $route = $request->route();

        return array_merge(
            $route ? $route->parametersWithoutNulls() : [],
            $request->all()
        );
    }
}