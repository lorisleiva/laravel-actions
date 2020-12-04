<?php

namespace Lorisleiva\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ActionRequest extends FormRequest
{
    use DecorateActions;

    public function validateResolved()
    {
        // Cancel the auto-resolution trait.
    }

    public function resolve()
    {
        // Manually resolve authorization and validation.
        parent::validateResolved();
    }

    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = $this->container->make(ValidationFactory::class);

        if ($this->hasMethod('getValidator')) {
            $validator = $this->resolveAndCall('getValidator', compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if ($this->hasMethod('withValidator')) {
            $this->resolveAndCall('withValidator', compact('validator'));
        }

        if ($this->hasMethod('afterValidator')) {
            $validator->after(function ($validator) {
                $this->resolveAndCall('afterValidator', compact('validator'));
            });
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    protected function createDefaultValidator(ValidationFactory $factory)
    {
        return $factory->make(
            $this->validationData(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
    }

    public function validationData()
    {
        return $this->hasMethod('getValidationData')
            ? $this->resolveAndCall('getValidationData')
            : $this->all();
    }

    public function rules()
    {
        return $this->hasMethod('rules')
            ? $this->resolveAndCall('rules')
            : [];
    }

    public function messages()
    {
        return $this->hasMethod('getValidationMessages')
            ? $this->resolveAndCall('getValidationMessages')
            : [];
    }

    public function attributes()
    {
        return $this->hasMethod('getValidationAttributes')
            ? $this->resolveAndCall('getValidationAttributes')
            : [];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->hasMethod('getValidationFailure')) {
            return $this->resolveAndCall('getValidationFailure', compact('validator'));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->getErrorBag($validator))
            ->redirectTo($this->getRedirectUrl());
    }

    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        return $this->hasMethod('getValidationRedirect')
            ? $this->resolveAndCall('getValidationRedirect', compact('url'))
            : $url->previous();
    }

    protected function getErrorBag(Validator $validator)
    {
        return $this->hasMethod('getValidationErrorBag')
            ? $this->resolveAndCall('getValidationErrorBag', compact('validator'))
            : 'default';
    }

    protected function passesAuthorization()
    {
        return $this->hasMethod('authorize')
            ? $this->resolveAndCall('authorize')
            : true;
    }

    protected function failedAuthorization()
    {
        if ($this->hasMethod('getAuthorizationFailure')) {
            return $this->resolveAndCall('getAuthorizationFailure');
        }

        throw new AuthorizationException;
    }

    public function validated()
    {
        return $this->validator->validated();
    }

    protected function prepareForValidation()
    {
        if ($this->hasMethod('prepareForValidation')) {
            return $this->resolveAndCall('prepareForValidation');
        }
    }

    protected function passedValidation()
    {
        if ($this->hasMethod('passedValidation')) {
            return $this->resolveAndCall('passedValidation');
        }
    }
}
