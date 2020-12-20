<?php

namespace Lorisleiva\Actions;

use Illuminate\Contracts\Foundation\Application;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\DesignPatterns\DesignPattern;

class ActionManager
{
    /** @var Application */
    protected Application $app;

    /** @var DesignPattern[] */
    protected array $designPatterns = [];

    /** @var bool[] */
    protected array $extended = [];

    public function __construct(Application $app, array $designPatterns = [])
    {
        $this->app = $app;
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

    public function getDesignPatternsMatching(array $usedTraits): array
    {
        $filter = function (DesignPattern $designPattern) use ($usedTraits) {
            return in_array($designPattern->getTrait(), $usedTraits);
        };

        return array_filter($this->getDesignPatterns(), $filter);
    }

    public function extend(string $abstract): void
    {
        if ($this->isExtending($abstract)) {
            return;
        }

        if (! $this->shouldExtend($abstract)) {
            return;
        }

        $this->app->extend($abstract, function ($instance) {
            return $this->identifyAndDecorate($instance);
        });

        $this->extended[$abstract] = true;
    }

    public function isExtending(string $abstract): bool
    {
        return isset($this->extended[$abstract]);
    }

    public function shouldExtend(string $abstract): bool
    {
        $usedTraits = class_uses_recursive($abstract);

        return ! empty($this->getDesignPatternsMatching($usedTraits))
            || in_array(AsFake::class, $usedTraits);
    }

    public function identifyAndDecorate($instance, $limit = 10)
    {
        $usedTraits = class_uses_recursive($instance);

        if (in_array(AsFake::class, $usedTraits) && $instance::isFake()) {
            $instance = $instance::mock();
        }

        if (! $designPattern = $this->identifyFromBacktrace($usedTraits, $limit)) {
            return $instance;
        }

        return $designPattern->decorate($instance);
    }

    public function identifyFromBacktrace($usedTraits, $limit = 10): ?DesignPattern
    {
        $designPatterns = $this->getDesignPatternsMatching($usedTraits);

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit) as $frame) {
            $frame = new BacktraceFrame($frame);

            /** @var DesignPattern $designPattern */
            foreach ($designPatterns as $designPattern) {
                if ($designPattern->recognizeFrame($frame)) {
                    return $designPattern;
                }
            }
        }

        return null;
    }
}
