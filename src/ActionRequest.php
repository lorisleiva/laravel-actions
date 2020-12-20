<?php

namespace Lorisleiva\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
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
        $this->prepareForValidation();
        $response = $this->inspectAuthorization();

        if (! $response->allowed()) {
            $this->deniedAuthorization($response);
        }

        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }
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

    protected function inspectAuthorization(): Response
    {
        try {
            $response = $this->hasMethod('authorize')
                ? $this->resolveAndCallMethod('authorize')
                : true;
        } catch (AuthorizationException $e) {
            return $e->toResponse();
        }

        if ($response instanceof Response) {
            return $response;
        }

        return $response ? Response::allow() : Response::deny();
    }

    protected function deniedAuthorization(Response $response): void
    {
        if ($this->hasMethod('getAuthorizationFailure')) {
            $this->resolveAndCallMethod('getAuthorizationFailure', compact('response'));

            return;
        }

        $response->authorize();
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
