<?php

namespace Lorisleiva\Actions;

use Illuminate\Routing\Redirector;
use Lorisleiva\Actions\Concerns\ValidateActions;

class AttributeValidator
{
    use ValidateActions;

    public function __construct($action)
    {
        $this->setAction($action);
        $this->redirector = app(Redirector::class);
    }

    public static function for($action): self
    {
        return new static($action);
    }

    public function getDefaultValidationData(): array
    {
        return $this->fromActionMethod('all');
    }
}
