<?php

namespace Lorisleiva\Actions\Concerns;

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

    public function all(): array
    {
        return $this->attributes;
    }

    public function only($keys)
    {
        return Arr::only($this->attributes, is_array($keys) ? $keys : func_get_args());
    }

    public function except($keys)
    {
        return Arr::except($this->attributes, is_array($keys) ? $keys : func_get_args());
    }

    public function has($key)
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
}
