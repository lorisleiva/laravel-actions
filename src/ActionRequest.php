<?php

namespace Lorisleiva\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\DecorateActions;

class ActionRequest extends FormRequest
{
    use RouteDependencyResolverTrait;
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
            $validator = $this->resolveFromRouteAndCall('getValidator', compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if ($this->hasMethod('withValidator')) {
            $this->resolveFromRouteAndCall('withValidator', compact('validator'));
        }

        if ($this->hasMethod('afterValidator')) {
            $validator->after(function ($validator) {
                $this->resolveFromRouteAndCall('afterValidator', compact('validator'));
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
            ? $this->resolveFromRouteAndCall('getValidationData')
            : $this->all();
    }

    public function rules()
    {
        return $this->hasMethod('rules')
            ? $this->resolveFromRouteAndCall('rules')
            : [];
    }

    public function messages()
    {
        return $this->hasMethod('getValidationMessages')
            ? $this->resolveFromRouteAndCall('getValidationMessages')
            : [];
    }

    public function attributes()
    {
        return $this->hasMethod('getValidationAttributes')
            ? $this->resolveFromRouteAndCall('getValidationAttributes')
            : [];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->hasMethod('getValidationFailure')) {
            return $this->resolveFromRouteAndCall('getValidationFailure', compact('validator'));
        }

        throw (new ValidationException($validator))
            ->errorBag($this->getErrorBag($validator))
            ->redirectTo($this->getRedirectUrl());
    }

    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        return $this->hasMethod('getValidationRedirect')
            ? $this->resolveFromRouteAndCall('getValidationRedirect', compact('url'))
            : $url->previous();
    }

    protected function getErrorBag(Validator $validator)
    {
        return $this->hasMethod('getValidationErrorBag')
            ? $this->resolveFromRouteAndCall('getValidationErrorBag', compact('validator'))
            : 'default';
    }

    protected function inspectAuthorization(): Response
    {
        try {
            $response = $this->hasMethod('authorize')
                ? $this->resolveFromRouteAndCall('authorize')
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
            $this->resolveFromRouteAndCall('getAuthorizationFailure', compact('response'));

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
            return $this->resolveFromRouteAndCall('prepareForValidation');
        }
    }

    protected function resolveFromRouteAndCall($method, $extraParameters = [])
    {
        $parameters = array_merge(
            $this->route()->parametersWithoutNulls(),
            $extraParameters,
        );

        $arguments = $this->resolveClassMethodDependencies(
            $parameters,
            $this->action,
            $method
        );

        return $this->action->{$method}(...array_values($arguments));
    }
}
