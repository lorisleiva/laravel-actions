<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Auth\Access\AuthorizationException;

trait VerifyAuthorization
{
    protected function resolveAuthorization()
    {
        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        return $this;
    }

    public function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }

        return true;
    }
    
    protected function failedAuthorization()
    {
        throw new AuthorizationException('This action is unauthorized.');
    }
}