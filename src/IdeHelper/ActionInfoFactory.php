<?php

namespace Lorisleiva\Actions\IdeHelper;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsObject;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Php\File;
use phpDocumentor\Reflection\Php\ProjectFactory;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ActionInfoFactory
{
    /** @return array<\Lorisleiva\Actions\IdeHelper\ActionInfo> */
    public static function create(string $path): array
    {
        $factory = new self();
        $classes = $factory->loadFromPath($path);
        $classMap = $factory->loadPhpDocumentorReflectionClassMap($path);
        $ais = [];
        foreach ($classes as $class => $traits) {
            $tc = collect($traits);
            $ais[] = ActionInfo::create()
                ->setName($class)
                ->setAsObject($tc->contains(AsObject::class))
                ->setAsCommand($tc->contains(AsCommand::class))
                ->setAsController($tc->contains(AsController::class))
                ->setAsJob($tc->contains(AsJob::class))
                ->setAsListener($tc->contains(AsListener::class))
                ->setClassInfo($classMap[$class]);
        }
        return $ais;
    }

    /** @return array<class-string,array<class-string>> */
    protected function loadFromPath(string $path)
    {
        $finder = Finder::create()->files()->in($path)->name('*.php');
        $result = [];

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className === null) {
                continue;
            }

            if (! class_exists($className)) {
                require_once $file->getRealPath();
            }

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            $allTraits = $this->getAllTraits($reflection);
            $matchingTraits = array_values(array_intersect($allTraits, ActionInfo::ALL_TRAITS));

            if (! empty($matchingTraits)) {
                $result[$className] = $matchingTraits;
            }
        }

        return $result;
    }

    protected function getClassNameFromFile(SplFileInfo $file): ?string
    {
        $content = file_get_contents($file->getRealPath());

        if (
            preg_match('/namespace\s+(.+?);/', $content, $nsMatch)
            && preg_match('/class\s+(\w+)/', $content, $classMatch)
        ) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }

        return null;
    }

    /** @return array<class-string> */
    protected function getAllTraits(ReflectionClass $class): array
    {
        $traits = [];

        foreach ($class->getTraits() as $trait) {
            $traits[] = $trait->getName();
            $traits = array_merge($traits, $this->getTraitTraits($trait));
        }

        if ($parent = $class->getParentClass()) {
            $traits = array_merge($traits, $this->getAllTraits($parent));
        }

        return array_unique($traits);
    }

    /** @return array<class-string> */
    protected function getTraitTraits(ReflectionClass $trait): array
    {
        $traits = [];

        foreach ($trait->getTraits() as $t) {
            $traits[] = $t->getName();
            $traits = array_merge($traits, $this->getTraitTraits($t));
        }

        return $traits;
    }

    /**
     * @return array<\phpDocumentor\Reflection\Php\Class_>
     * @throws \phpDocumentor\Reflection\Exception
     */
    protected function loadPhpDocumentorReflectionClassMap(string $path): array
    {
        $finder = Finder::create()->files()->in($path)->name('*.php');
        $files = collect($finder)->map(fn(SplFileInfo $file) => new LocalFile($file->getRealPath()))->toArray();

        $project = ProjectFactory::createInstance()->create('Laravel Actions IDE Helper', $files);
        return collect($project->getFiles())
            ->map(fn(File $f) => $f->getClasses())
            ->collapse()
            ->mapWithKeys(fn($item, string $key) => [Str::of($key)->ltrim("\\")->toString() => $item])
            ->toArray();
    }
}
