<?php

namespace Lorisleiva\Actions\IdeHelper;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsObject;
use phpDocumentor\Reflection\Php\Class_;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsCommandGenerator;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsControllerGenerator;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsJobGenerator;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsListenerGenerator;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsObjectGenerator;

final class ActionInfo
{
    public string $name;
    public string $namespace;
    public string $fqsen;
    public bool $asObject;
    public bool $asController;
    public bool $asJob;
    public bool $asListener;
    public bool $asCommand;
    public Class_ $classInfo;

    const ALL_TRAITS = [
        AsObject::class,
        AsController::class,
        AsJob::class,
        AsListener::class,
        AsCommand::class,
        AsFake::class,
    ];

    public static function create(): ActionInfo
    {
        return new ActionInfo();
    }

    public function setName(string $name): ActionInfo
    {
        $this->fqsen = $name;
        $this->name = class_basename($name);
        $this->namespace = Str::of($name)->beforeLast('\\' . $this->name);

        return $this;
    }

    public function setAsObject(bool $asObject): ActionInfo
    {
        $this->asObject = $asObject;
        return $this;
    }

    public function setAsController(bool $asController): ActionInfo
    {
        $this->asController = $asController;
        return $this;
    }

    public function setAsJob(bool $asJob): ActionInfo
    {
        $this->asJob = $asJob;
        return $this;
    }

    public function setAsListener(bool $asListener): ActionInfo
    {
        $this->asListener = $asListener;
        return $this;
    }

    public function setAsCommand(bool $asCommand): ActionInfo
    {
        $this->asCommand = $asCommand;
        return $this;
    }

    public function setClassInfo(Class_ $classInfo): ActionInfo
    {
        $this->classInfo = $classInfo;
        return $this;
    }

    /** @return \Lorisleiva\Actions\IdeHelper\Generator\DocBlock\DocBlockGeneratorInterface[] */
    public function getGenerators(): array
    {
        return array_merge(
            ($this->asCommand ? [AsCommandGenerator::class] : []),
            ($this->asController ? [AsControllerGenerator::class] : []),
            ($this->asJob ? [AsJobGenerator::class] : []),
            ($this->asListener ? [AsListenerGenerator::class] : []),
            ($this->asObject ? [AsObjectGenerator::class] : []),
        );
    }
}
