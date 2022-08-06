<?php

namespace Lorisleiva\Actions\DesignPatterns;

use Lorisleiva\Actions\BacktraceFrame;

interface DesignPattern
{
    public function getTrait(): string;

    public function recognizeFrame(BacktraceFrame $frame): bool;

    public function decorate($instance, BacktraceFrame $frame);
}
