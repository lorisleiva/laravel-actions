<?php

use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Lorisleiva\Actions\IdeHelper\Generator\DocBlock\AsJobGenerator;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\Jobs\WithDecoratorAction;
use Lorisleiva\Actions\Tests\IdeHelper\stubs\Jobs\WithoutDecoratorAction;

it('runs without an error', function (string $class, array $expectations) {
    $ai = getIdeHelperActionInfo($class);

    $docblock = collect((new AsJobGenerator())->generate($ai));

    expect($docblock->count())->toEqual(8);

    $all = $docblock->map(fn($item) => $item->render())->implode(PHP_EOL);
    foreach ($expectations as $expectation) {
        expect($all)->toContain($expectation);
    }
})->with([
    'with decorator' => [
        WithDecoratorAction::class, [
            '@method static \\' . JobDecorator::class . '|\\' . UniqueJobDecorator::class . ' makeJob(int $i)',
        ],
    ],
    'without decorator' => [
        WithoutDecoratorAction::class, [
            '@method static \\' . JobDecorator::class . '|\\' . UniqueJobDecorator::class . ' makeJob()',
        ],
    ],
]);
