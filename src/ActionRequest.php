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
            $validator = $this->resolveAndCallMethod('getValidator', compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if ($this->hasMethod('withValidator')) {
            $this->resolveAndCallMethod('withValidator', compact('validator'));
        }

        if ($this->hasMethod('afterValidator')) {
            $validator->after(function ($validator) {
                $this->resolveAndCallMethod('afterValidator', compact('validator'));
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
            ? $this->resolveAndCallMethod('getValidationData')
            : $this->all();
    }

    public function rules()
    {
        return $this->hasMethod('rules')
            ? $this->resolveAndCallMethod('rules')
            : [];
    }

    public function messages()
    {
        return $this->hasMethod('getValidationMessages')
            ? $this->resolveAndCallMethod('getValidationMessages')
            : [];
    }

    public function attributes()
    {
        return $this->hasMethod('getValidationAttributes')
            ? $this->resolveAndCallMethod('getValidationAttributes')
            : [];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->hasMethod('getValidationFailure')) {
            return $this->resolveAndCallMethod('getValidationFailure', compact('validator'));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->getErrorBag($validator))
            ->redirectTo($this->getRedirectUrl());
    }

    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        return $this->hasMethod('getValidationRedirect')
            ? $this->resolveAndCallMethod('getValidationRedirect', compact('url'))
            : $url->previous();
    }

    protected function getErrorBag(Validator $validator)
    {
        return $this->hasMethod('getValidationErrorBag')
            ? $this->resolveAndCallMethod('getValidationErrorBag', compact('validator'))
            : 'default';
    }

    protected function passesAuthorization()
    {
        return $this->hasMethod('authorize')
            ? $this->resolveAndCallMethod('authorize')
            : true;
    }

    protected function failedAuthorization()
    {
        if ($this->hasMethod('getAuthorizationFailure')) {
            return $this->resolveAndCallMethod('getAuthorizationFailure');
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
            return $this->resolveAndCallMethod('prepareForValidation');
        }
    }
}
