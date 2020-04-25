<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Support\Arr;

trait HasAttributes
{
    protected $attributes = [];

    public function setRawAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function fill(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    public function all()
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

    public function set($key, $value)
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

    public function __isset($key)
    {
        return ! is_null($this->get($key));
    }
}
