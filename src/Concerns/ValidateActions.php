<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;

trait ValidateActions
{
    use DecorateActions;

    /** @var Validator|null */
    protected $validator = null;

    /** @var Redirector|null */
    protected $redirector = null;

    public function validate()
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

    protected function getValidatorInstance(): Validator
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = app(ValidationFactory::class);

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

        return $this->validator = $validator;
    }

    protected function createDefaultValidator(ValidationFactory $factory): Validator
    {
        return $factory->make(
            $this->validationData(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
    }

    public function validationData(): array
    {
        return $this->hasMethod('getValidationData')
            ? $this->resolveAndCallMethod('getValidationData')
            : $this->getDefaultValidationData();
    }

    public function rules(): array
    {
        return $this->hasMethod('rules')
            ? $this->resolveAndCallMethod('rules')
            : [];
    }

    public function messages(): array
    {
        return $this->hasMethod('getValidationMessages')
            ? $this->resolveAndCallMethod('getValidationMessages')
            : [];
    }

    public function attributes(): array
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

    protected function getErrorBag(Validator $validator): string
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

    public function validated($key = null, $default = null): mixed
    {
        return data_get($this->validator->validated(), $key, $default);
    }

    protected function prepareForValidation()
    {
        if ($this->hasMethod('prepareForValidation')) {
            return $this->resolveAndCallMethod('prepareForValidation');
        }
    }
}
