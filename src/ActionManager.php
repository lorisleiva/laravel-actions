<?php

namespace Lorisleiva\Actions;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Queue\Interruptible;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Decorators\DebounceJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleDebounceJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleUniqueJobDecorator;
use Lorisleiva\Actions\Decorators\InterruptibleUniqueUntilProcessingJobDecorator;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Lorisleiva\Actions\Decorators\UniqueUntilProcessingJobDecorator;
use Lorisleiva\Actions\DesignPatterns\DesignPattern;
use Lorisleiva\Lody\Lody;

class ActionManager
{
    /** @var class-string<JobDecorator> */
    public static string $jobDecorator = JobDecorator::class;

    /** @var class-string<UniqueJobDecorator> */
    public static string $uniqueJobDecorator = UniqueJobDecorator::class;

    /** @var class-string<UniqueJobDecorator&ShouldBeUniqueUntilProcessing> */
    public static string $uniqueUntilProcessingJobDecorator = UniqueUntilProcessingJobDecorator::class;

    /** @var class-string<DebounceJobDecorator> */
    public static string $debounceJobDecorator = DebounceJobDecorator::class;

    /** @var class-string<JobDecorator&Interruptible> */
    public static string $interruptibleJobDecorator = InterruptibleJobDecorator::class;

    /** @var class-string<UniqueJobDecorator&Interruptible> */
    public static string $interruptibleUniqueJobDecorator = InterruptibleUniqueJobDecorator::class;

    /** @var class-string<UniqueJobDecorator&ShouldBeUniqueUntilProcessing&Interruptible> */
    public static string $interruptibleUniqueUntilProcessingJobDecorator = InterruptibleUniqueUntilProcessingJobDecorator::class;

    /** @var class-string<DebounceJobDecorator&Interruptible> */
    public static string $interruptibleDebounceJobDecorator = InterruptibleDebounceJobDecorator::class;

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
     * @param class-string<UniqueJobDecorator> $uniqueJobDecoratorClass
     */
    public static function useUniqueJobDecorator(string $uniqueJobDecoratorClass): void
    {
        static::$uniqueJobDecorator = $uniqueJobDecoratorClass;
    }

    /**
     * @param class-string<UniqueJobDecorator&ShouldBeUniqueUntilProcessing> $uniqueUntilProcessingJobDecoratorClass
     */
    public static function useUniqueUntilProcessingJobDecorator(string $uniqueUntilProcessingJobDecoratorClass): void
    {
        static::$uniqueUntilProcessingJobDecorator = $uniqueUntilProcessingJobDecoratorClass;
    }

    /**
     * @param class-string<DebounceJobDecorator> $debounceJobDecoratorClass
     */
    public static function useDebounceJobDecorator(string $debounceJobDecoratorClass): void
    {
        static::$debounceJobDecorator = $debounceJobDecoratorClass;
    }

    /**
     * @param class-string<JobDecorator&Interruptible> $interruptibleJobDecoratorClass
     */
    public static function useInterruptibleJobDecorator(string $interruptibleJobDecoratorClass): void
    {
        static::$interruptibleJobDecorator = $interruptibleJobDecoratorClass;
    }

    /**
     * @param class-string<UniqueJobDecorator&Interruptible> $interruptibleUniqueJobDecoratorClass
     */
    public static function useInterruptibleUniqueJobDecorator(string $interruptibleUniqueJobDecoratorClass): void
    {
        static::$interruptibleUniqueJobDecorator = $interruptibleUniqueJobDecoratorClass;
    }

    /**
     * @param class-string<UniqueJobDecorator&ShouldBeUniqueUntilProcessing&Interruptible> $interruptibleUniqueUntilProcessingJobDecoratorClass
     */
    public static function useInterruptibleUniqueUntilProcessingJobDecorator(string $interruptibleUniqueUntilProcessingJobDecoratorClass): void
    {
        static::$interruptibleUniqueUntilProcessingJobDecorator = $interruptibleUniqueUntilProcessingJobDecoratorClass;
    }

    /**
     * @param class-string<DebounceJobDecorator&Interruptible> $interruptibleDebounceJobDecoratorClass
     */
    public static function useInterruptibleDebounceJobDecorator(string $interruptibleDebounceJobDecoratorClass): void
    {
        static::$interruptibleDebounceJobDecorator = $interruptibleDebounceJobDecoratorClass;
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

    public function identifyFromBacktrace($usedTraits, ?BacktraceFrame &$frame = null): ?DesignPattern
    {
        $designPatterns = $this->getDesignPatternsMatching($usedTraits);
        $backtraceOptions = DEBUG_BACKTRACE_PROVIDE_OBJECT
            | DEBUG_BACKTRACE_IGNORE_ARGS;
        
        $ownNumberOfFrames = 2;
        $frames = array_slice(
            debug_backtrace($backtraceOptions, $ownNumberOfFrames + $this->backtraceLimit),
            $ownNumberOfFrames
        );
        foreach ($frames as $rawFrame) {
            $frame = new BacktraceFrame($rawFrame);

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
