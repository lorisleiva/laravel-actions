<?php

namespace Lorisleiva\Actions;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Lorisleiva\Actions\DesignPatterns\DesignPattern;
use Lorisleiva\Lody\Lody;

class ActionManager
{
    /** @var class-string<JobDecorator> */
    public static string $jobDecorator = JobDecorator::class;

    /** @var class-string<JobDecorator&ShouldBeUnique> */
    public static string $uniqueJobDecorator = UniqueJobDecorator::class;

    /** @var DesignPattern[] */
    protected array $designPatterns = [];

    /** @var bool[] */
    protected array $extended = [];

    protected int $backtraceLimit = 10;

    public function __construct(array $designPatterns = [])
    {
        $this->setDesignPatterns($designPatterns);
    }

    /**
     * @param class-string<JobDecorator> $jobDecoratorClass
     */
    public static function useJobDecorator(string $jobDecoratorClass): void
    {
        static::$jobDecorator = $jobDecoratorClass;
    }

    /**
     * @param class-string<JobDecorator&ShouldBeUnique> $uniqueJobDecoratorClass
     */
    public static function useUniqueJobDecorator(string $uniqueJobDecoratorClass): void
    {
        static::$uniqueJobDecorator = $uniqueJobDecoratorClass;
    }

    public function setBacktraceLimit(int $backtraceLimit): ActionManager
    {
        $this->backtraceLimit = $backtraceLimit;

        return $this;
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

    public function registerDesignPattern(DesignPattern $designPattern): ActionManager
    {
        $this->designPatterns[] = $designPattern;
        
        return $this;
    }

    public function getDesignPatternsMatching(array $usedTraits): array
    {
        $filter = function (DesignPattern $designPattern) use ($usedTraits) {
            return in_array($designPattern->getTrait(), $usedTraits);
        };

        return array_filter($this->getDesignPatterns(), $filter);
    }

    public function extend(Application $app, string $abstract): void
    {
        if ($this->isExtending($abstract)) {
            return;
        }

        if (! $this->shouldExtend($abstract)) {
            return;
        }

        $app->extend($abstract, function ($instance) {
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

    public function identifyAndDecorate($instance)
    {
        $usedTraits = class_uses_recursive($instance);

        if (in_array(AsFake::class, $usedTraits) && $instance::isFake()) {
            $instance = $instance::mock();
        }

        if (! $designPattern = $this->identifyFromBacktrace($usedTraits, $frame)) {
            return $instance;
        }

        return $designPattern->decorate($instance, $frame);
    }

    public function identifyFromBacktrace($usedTraits, BacktraceFrame &$frame = null): ?DesignPattern
    {
        $designPatterns = $this->getDesignPatternsMatching($usedTraits);
        $backtraceOptions = DEBUG_BACKTRACE_PROVIDE_OBJECT
            | DEBUG_BACKTRACE_IGNORE_ARGS;
        
        $ownNumberOfFrames = 2;
        $frames = array_slice(
            debug_backtrace($backtraceOptions, $ownNumberOfFrames + $this->backtraceLimit),
            $ownNumberOfFrames
        );
        foreach ($frames as $frame) {
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

    public function registerRoutes(array | string $paths = 'app/Actions'): void
    {
        Lody::classes($paths)
            ->isNotAbstract()
            ->hasTrait(AsController::class)
            ->hasStaticMethod('routes')
            ->each(fn (string $classname) => $this->registerRoutesForAction($classname));
    }

    public function registerCommands(array | string $paths = 'app/Actions'): void
    {
        Lody::classes($paths)
            ->isNotAbstract()
            ->hasTrait(AsCommand::class)
            ->filter(function (string $classname): bool {
                return property_exists($classname, 'commandSignature')
                    || method_exists($classname, 'getCommandSignature');
            })
            ->each(fn (string $classname) => $this->registerCommandsForAction($classname));
    }

    public function registerRoutesForAction(string $className): void
    {
        $className::routes(app(Router::class));
    }

    public function registerCommandsForAction(string $className): void
    {
        Artisan::starting(function ($artisan) use ($className) {
            $artisan->resolve($className);
        });
    }
}
