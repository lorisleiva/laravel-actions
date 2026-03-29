<?php

namespace Lorisleiva\Actions\IdeHelper\Generator\DocBlock;

use Lorisleiva\Actions\IdeHelper\ActionInfo;

interface DocBlockGeneratorInterface
{
    public static function create(): self;

    /** @return \phpDocumentor\Reflection\DocBlock\Tag[] */
    public function generate(ActionInfo $info): array;
}
