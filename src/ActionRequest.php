<?php

namespace Lorisleiva\Actions;

use Lorisleiva\Actions\Concerns\DecorateActions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

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

        if ($this->hasMethod('getRequestValidator')) {
            $validator = $this->resolveAndCall('getRequestValidator', compact('factory'));
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
        return $this->hasMethod('getRequestValidationData')
            ? $this->resolveAndCall('getRequestValidationData')
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
        return $this->hasMethod('getRequestMessages')
            ? $this->resolveAndCall('getRequestMessages')
            : [];
    }

    public function attributes()
    {
        return $this->hasMethod('getRequestAttributes')
            ? $this->resolveAndCall('getRequestAttributes')
            : [];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->hasMethod('getRequestValidationFailure')) {
            return $this->resolveAndCall('getRequestValidationFailure', compact('validator'));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->getErrorBag($validator))
            ->redirectTo($this->getRedirectUrl());
    }

    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        return $this->hasMethod('getRequestValidationRedirect')
            ? $this->resolveAndCall('getRequestValidationRedirect', compact('url'))
            : $url->previous();
    }

    protected function getErrorBag(Validator $validator)
    {
        return $this->hasMethod('getRequestErrorBag')
            ? $this->resolveAndCall('getRequestErrorBag', compact('validator'))
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
        if ($this->hasMethod('getRequestAuthorizationFailure')) {
            return $this->resolveAndCall('getRequestAuthorizationFailure');
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
