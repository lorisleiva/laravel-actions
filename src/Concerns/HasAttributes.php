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
        return Arr::only($this->attributes, $keys);
    }

    public function except($keys)
    {
        return Arr::except($this->attributes, $keys);
    }

    public function getAttribute($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function setAttribute($key, $value)
    {
        Arr::set($this->attributes, $key, $value);

        return $this;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
}