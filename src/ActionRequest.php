<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Http\FormRequest;
use Lorisleiva\Actions\Concerns\ValidateActions;

class ActionRequest extends FormRequest
{
    use ValidateActions;

    public function validateResolved()
    {
        // Cancel the auto-resolution trait.
    }

    public function getDefaultValidationData(): array
    {
        return $this->all();
    }
}
