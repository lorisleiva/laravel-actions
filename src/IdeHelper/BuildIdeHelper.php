<?php

namespace Lorisleiva\Actions\IdeHelper;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Serializer;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use PhpParser\Builder\Trait_;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\PrettyPrinter\Standard;

class BuildIdeHelper
{
    public static function create(): BuildIdeHelper
    {
        return new BuildIdeHelper();
    }

    /** @param \Lorisleiva\Actions\IdeHelper\ActionInfo[] $actionInfos */
    public function build(array $actionInfos): string
    {
        $groups = collect($actionInfos)->groupBy(function (ActionInfo $item) {
            return $item->namespace;
        })->toArray();

        $nodes = [];
        $factory = new BuilderFactory();
        foreach ($groups as $namespace => $items) {
            $ns = $factory->namespace($namespace);
            foreach ($items as $item) {
                $ns->addStmt($factory->class($item->classInfo->getName())->setDocComment(new Doc($this->generateDocBlocks($item))));
            }
            $nodes[] = $ns->getNode();
        }
        $nodes[] = $this->getTraitIdeHelpers($factory);
        $printer = new Standard();
        return $printer->prettyPrintFile($nodes);
    }

    protected function generateDocBlocks(ActionInfo $info): string
    {
        $tags = [];
        foreach ($info->getGenerators() as $generator) {
            $tags = array_merge($tags, $generator::create()->generate($info));
        }
        return $this->serializeDocBlocks(...$tags);
    }

    protected function serializeDocBlocks(Tag ...$tags): string
    {
        $db = new DocBlock('', null, $tags);
        $serializer = new Serializer();
        return $serializer->getDocComment($db);
    }

    protected function resolveType(string $type): Type
    {
        return (new TypeResolver())->resolve($type);
    }

    protected function resolveAsUnionType(string ...$types): Type
    {
        return (new TypeResolver())->resolve(implode('|', $types));
    }

    protected function getTraitIdeHelpers(BuilderFactory $factory): \PhpParser\Node
    {
        return $factory->namespace("Lorisleiva\Actions\Concerns")
            ->addStmt(
                (new Trait_("AsController"))->setDocComment(
                    $this->serializeDocBlocks(
                        new DocBlock\Tags\Method('asController', [], $this->resolveType('void'))
                    )
                )
            )->addStmt(
                (new Trait_("AsListener"))->setDocComment(
                    $this->serializeDocBlocks(
                        new DocBlock\Tags\Method('asListener', [], $this->resolveType('void'))
                    )
                )
            )->addStmt(
                (new Trait_("AsJob"))->setDocComment(
                    $this->serializeDocBlocks(
                        new DocBlock\Tags\Method('asJob', [], $this->resolveType('void'))
                    )
                )
            )
            ->addStmt(
                (new Trait_("AsCommand"))->setDocComment(
                    $this->serializeDocBlocks(
                        new DocBlock\Tags\Method('asCommand', arguments: [
                            ['name' => 'command', 'type' => $this->resolveType("\Illuminate\Console\Command")],
                        ], returnType: $this->resolveType('void'))
                    )
                )
            )
            ->getNode();
    }
}
