<?php

namespace Lorisleiva\Actions;

use Lorisleiva\Actions\DesignPatterns\DesignPattern;

class ActionManager
{
    /** @var DesignPattern[] */
    protected array $designPatterns;

    public function __construct(array $designPatterns = [])
    {
        $this->setDesignPatterns($designPatterns);
    }

    public function setDesignPatterns(array $designPatterns): ActionManager
    {
        $this->designPatterns = $designPatterns;

        return $this;
    }

    public function getDesignPatterns(): array
    {
        return $this->designPatterns;
    }

    public function getDesignPatternsFor($instance): array
    {
        $usedTraits = class_uses_recursive($instance);

        $filter = function (DesignPattern $designPattern) use ($usedTraits) {
            return in_array($designPattern->getTrait(), $usedTraits);
        };

        return array_filter($this->getDesignPatterns(), $filter);
    }

    public function identifyAndDecorate($instance, $limit = 10)
    {
        if (! $designPattern = $this->identifyFromBacktrace($instance, $limit)) {
            return $instance;
        }

        return $designPattern->decorate($instance);
    }

    public function identifyFromBacktrace($instance, $limit = 10): ?DesignPattern
    {
        $designPatterns = $this->getDesignPatternsFor($instance);

        foreach (debug_backtrace(2, $limit) as $frame) {
            $frame = new BacktraceFrame($frame);

            foreach ($designPatterns as $designPattern) {
                if ($designPattern->recognizeFrame($frame)) {
                    return $designPattern;
                }
            }
        }

        return null;
    }
}
