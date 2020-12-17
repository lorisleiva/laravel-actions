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

    public function getDesignPatternsFor($instance): array
    {
        $usedTraits = class_uses_recursive($instance);

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

        $usesAsFakeTrait = in_array(AsFake::class, class_uses_recursive($abstract));

        if (empty($this->getDesignPatternsFor($abstract)) && ! $usesAsFakeTrait) {
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

    public function identifyAndDecorate($instance, $limit = 10)
    {
        $instanceOrFake = (method_exists($instance, 'isFake') && $instance::isFake()) ? $instance::mock() : $instance;

        if (! $designPattern = $this->identifyFromBacktrace($instance, $limit)) {
            return $instanceOrFake;
        }

        return $designPattern->decorate($instanceOrFake);
    }

    public function identifyFromBacktrace($instance, $limit = 10): ?DesignPattern
    {
        $designPatterns = $this->getDesignPatternsFor($instance);

        foreach (debug_backtrace(2, $limit) as $frame) {
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
