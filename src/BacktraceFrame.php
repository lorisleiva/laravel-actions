<?php

namespace Lorisleiva\Actions;

class BacktraceFrame
{
    public ?string $class;
    public ?string $function;
    public bool $isStatic;
    public mixed $object;

    public function __construct(array $frame)
    {
        $this->class = $frame['class'] ?? null;
        $this->function = $frame['function'] ?? null;
        $this->isStatic = ($frame['type'] ?? null) === '::';
        $this->object = $frame['object'] ?? null;
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

    public function matches(string $class, string $method, ?bool $isStatic = null): bool
    {
        return $this->function === $method
            && (is_null($isStatic) || $this->isStatic === $isStatic)
            && $this->instanceOf($class);
    }

    public function getObject()
    {
        return $this->object;
    }
}
