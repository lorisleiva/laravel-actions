<?php

namespace Lorisleiva\Actions\Routing\Middleware;

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Routing\Contracts\ControllerDispatcher;
use Lorisleiva\Actions\Routing\PrecognitionActionControllerDispatcher;

class HandlePrecognitiveActionRequests extends HandlePrecognitiveRequests
{

    /**
     * Prepare to handle a precognitive request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function prepareForPrecognition($request)
    {
        parent::prepareForPrecognition($request);

        $this->container->bind(ControllerDispatcher::class, PrecognitionActionControllerDispatcher::class);
    }
}
