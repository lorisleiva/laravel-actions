<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\Arr;

class BacktraceFrame
{
    /** @var string|null */
    public $class;

    /** @var string|null */
    public $function;

    /** @var bool */
    public $isStatic;

    public function __construct(array $frame)
    {
        $this->class = Arr::get($frame, 'class');
        $this->function = Arr::get($frame, 'function');
        $this->isStatic = Arr::get($frame, 'type') === '::';
    }

    public function fromClass(): bool
    {
        return ! is_null($this->class);
    }

    public function instanceOf(string $superClass): bool
    {
        if (! $this->fromClass()) {
            return false;
        }

        return $this->class === $superClass
            || is_subclass_of($this->class, $superClass);
    }

    public function matches(string $class, string $method, ?bool $isStatic = null)
    {
        $matchesStatic = is_null($isStatic) ? true : ($this->isStatic === $isStatic);

        return $this->instanceOf($class)
            && $this->function === $method
            && $matchesStatic;
    }
}