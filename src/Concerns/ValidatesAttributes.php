<?php

namespace Lorisleiva\Actions\Concerns;

use App\Actions\InternalValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ValidatesAttributes
{
    protected $errorBag = 'default';
    protected $validator;

    public function validate($http = false)
    {
        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        if (! $this->passesValidation()) {
            $http 
                ? $this->failedHttpValidation() 
                : $this->failedValidation();
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
    
    public function passesValidation()
    {
        return $this->getValidatorInstance()->passes();
    }

    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = app(ValidationFactory::class);

        $validator = method_exists($this, 'validator')
            ? $this->validator($factory)
            : $this->createDefaultValidator($factory);

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    protected function createDefaultValidator(ValidationFactory $factory)
    {
        return $factory->make(
            $this->validationData(), $this->rules(),
            $this->messages(), $this->attributes()
        );
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('This action is unauthorized.');
    }

    protected function failedValidation()
    {
        throw new InternalValidationException($this->validator, $this->errorBag);
    }

    protected function failedHttpValidation()
    {
        throw (new ValidationException($this->validator))
                    ->errorBag($this->errorBag)
                    ->redirectTo($this->getRedirectUrl());
    }

    protected function getRedirectUrl()
    {
        return redirect()->getUrlGenerator()->previous();
    }
    
    protected function validationData()
    {
        return $this->all();
    }

    public function validatedData()
    {
        return $this->validator->validated();
    }

    public function rules()
    {
        return [];
    }

    public function messages()
    {
        return [];
    }

    public function attributes()
    {
        return [];
    }
}