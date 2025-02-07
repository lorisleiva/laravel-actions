<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Http\FormRequest;
use Lorisleiva\Actions\Concerns\ValidateActions;

class ActionRequest extends FormRequest
{
    use ValidateActions;

    public function validateResolved(): void
    {
        // Only run auto-resolution trait for precognitive requests.
        if (request()->isPrecognitive()) {
            parent::validateResolved();
        }
    }

    public function getDefaultValidationData(): array
    {
        return $this->all();
    }
}
