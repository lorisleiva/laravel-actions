<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\AttributeValidator;

/**
 * @method void prepareForValidation(\Lorisleiva\Actions\ActionRequest $request)
 * @method bool|\Illuminate\Auth\Access\Response authorize(\Lorisleiva\Actions\ActionRequest $request)
 * @method array rules()
 * @method void withValidator(\Illuminate\Validation\Validator $validator, \Lorisleiva\Actions\ActionRequest $request)
 * @method void afterValidator(\Illuminate\Validation\Validator $validator, \Lorisleiva\Actions\ActionRequest $request)
 * @method \Illuminate\Validation\Validator getValidator(\Illuminate\Validation\Factory $factory, \Lorisleiva\Actions\ActionRequest $request)
 * @method array getValidationData(\Lorisleiva\Actions\ActionRequest $request)
 * @method array getValidationMessages()
 * @method array getValidationAttributes()
 * @method string getValidationRedirect(\Illuminate\Routing\UrlGenerator $url)
 * @method string getValidationErrorBag()
 * @method void getValidationFailure()
 * @method void getAuthorizationFailure()
 */
trait WithAttributes
{
    protected array $attributes = [];

    /**
     * @param array $attributes
     * @return static
     */
    public function setRawAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param array $attributes
     * @return static
     */
    public function fill(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * @param Request $request
     * @return static
     */
    public function fillFromRequest(Request $request): self
    {
        $route = $request->route();

        $this->attributes = array_merge(
            $this->attributes,
            $route ? $route->parametersWithoutNulls() : [],
            $request->all(),
        );

        return $this;
    }

    public function all(): array
    {
        return $this->attributes;
    }

    public function only($keys): array
    {
        return Arr::only($this->attributes, is_array($keys) ? $keys : func_get_args());
    }

    public function except($keys): array
    {
        return Arr::except($this->attributes, is_array($keys) ? $keys : func_get_args());
    }

    public function has($key): bool
    {
        return Arr::has($this->attributes, $key);
    }

    public function get($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function set(string $key, $value): self
    {
        Arr::set($this->attributes, $key, $value);

        return $this;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __isset($key): bool
    {
        return ! is_null($this->get($key));
    }

    public function validateAttributes(): array
    {
        $validator = AttributeValidator::for($this);
        $validator->validate();

        return $validator->validated();
    }
}
