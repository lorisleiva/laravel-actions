<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Lorisleiva\Actions\BacktraceFrame;

abstract class DesignPattern
{
    abstract public function getTrait(): string;

    abstract public function recognizeFrame(BacktraceFrame $frame): bool;

    abstract public function decorate($instance, BacktraceFrame $frame);
}
