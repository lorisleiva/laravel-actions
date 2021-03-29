<?php

namespace Lorisleiva\Actions;

use Illuminate\Container\Container;
use Illuminate\Routing\Redirector;
use Lorisleiva\Actions\Concerns\ValidateActions;

class ActionValidator
{
    use ValidateActions;

    public function __construct($action)
    {
        $this->setAction($action);
        $this->setContainer(Container::getInstance());
        $this->redirector = $this->container->make(Redirector::class);
    }
}
