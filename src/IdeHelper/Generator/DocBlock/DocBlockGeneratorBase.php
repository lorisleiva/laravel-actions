<?php

namespace Lorisleiva\Actions\IdeHelper\Generator\DocBlock;

use phpDocumentor\Reflection\Php\Argument;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use Lorisleiva\Actions\IdeHelper\ActionInfo;

class DocBlockGeneratorBase implements DocBlockGeneratorInterface
{
    public static function create(): static
    {
        return new static();
    }

    protected function resolveType(string $type): Type
    {
        return (new TypeResolver())->resolve($type);
    }

    protected function resolveAsUnionType(string ...$types): Type
    {
        return (new TypeResolver())->resolve(implode('|', $types));
    }

    public function generate(ActionInfo $info): array
    {
        return [];
    }

    protected function convertArguments(array $arguments): array
    {
        return collect($arguments)
            ->transform(fn(Argument $arg) => ['name' => $arg->getName(), 'type' => $arg->getType()])
            ->toArray();
    }

    protected function findMethod(ActionInfo $info, string ...$methods): ?\phpDocumentor\Reflection\Php\Method
    {
        foreach ($methods as $method) {
            $m = collect($info->classInfo->getMethods())
                ->filter(fn(\phpDocumentor\Reflection\Php\Method $m) => $m->getName() == $method)
                ->first();
            if (!empty($m)) {
                return $m;
            }
        }
        return null;
    }
}
