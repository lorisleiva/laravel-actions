<?php

namespace Lorisleiva\Actions\IdeHelper\Generator\DocBlock;

use Lorisleiva\Actions\IdeHelper\ActionInfo;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\Custom\Method;

class AsObjectGenerator extends DocBlockGeneratorBase implements DocBlockGeneratorInterface
{
    public function generate(ActionInfo $info): array
    {
        $method = $this->findMethod($info, 'handle');
        return $method == null ? [] : [new Method('run', $method->getArguments(), $method->getReturnType(), true)];
    }
}
