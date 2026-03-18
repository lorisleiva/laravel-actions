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
use ReflectionClass;
use Symfony\Component\Finder\Finder;

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

    public function registerRoutes(array|string $paths = 'app/Actions'): void
    {
        $this->discoverClasses($paths)
            ->filter(fn (string $class) => ! (new ReflectionClass($class))->isAbstract())
            ->filter(fn (string $class) => in_array(AsController::class, class_uses_recursive($class)))
            ->filter(fn (string $class) => method_exists($class, 'routes'))
            ->each(fn (string $classname) => $this->registerRoutesForAction($classname));
    }

    public function registerCommands(array|string $paths = 'app/Actions'): void
    {
        $this->discoverClasses($paths)
            ->filter(fn (string $class) => ! (new ReflectionClass($class))->isAbstract())
            ->filter(fn (string $class) => in_array(AsCommand::class, class_uses_recursive($class)))
            ->filter(fn (string $class) => property_exists($class, 'commandSignature') || method_exists($class, 'getCommandSignature'))
            ->each(fn (string $classname) => $this->registerCommandsForAction($classname));
    }

    /**
     * Discover class names from the given paths.
     *
     * @param  array|string  $paths
     * @return \Illuminate\Support\Collection<int, class-string>
     */
    private function discoverClasses(array|string $paths): \Illuminate\Support\Collection
    {
        $paths = array_map(
            fn (string $path) => is_dir($path) ? $path : base_path($path),
            (array) $paths
        );

        $paths = array_filter($paths, 'is_dir');

        if (empty($paths)) {
            return collect();
        }

        $files = Finder::create()->files()->name('*.php')->in($paths)->sortByName();

        return collect($files)
            ->map(fn (\SplFileInfo $file) => $this->classFromFile($file))
            ->filter(fn (?string $class) => $class !== null && class_exists($class))
            ->values();
    }

    /**
     * Extract the FQCN from a PHP file using token parsing.
     */
    private function classFromFile(\SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getRealPath());
        $namespace = null;
        $class = null;

        $tokens = token_get_all($contents);
        foreach ($tokens as $i => $token) {
            if (! is_array($token)) {
                continue;
            }
            if ($token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_NAME_QUALIFIED, T_STRING])) {
                        $namespace .= $tokens[$j][1];
                    } elseif ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                }
            }
            if ($token[0] === T_CLASS) {
                // Skip anonymous classes
                if (isset($tokens[$i - 1]) && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_NEW) {
                    continue;
                }
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break;
                    }
                }
                break;
            }
        }

        if ($class === null) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
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
