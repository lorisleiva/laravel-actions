<?php

use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
use Lorisleiva\Actions\IdeHelper\ActionInfo;
use Lorisleiva\Actions\IdeHelper\ActionInfoFactory;
use Lorisleiva\Actions\Tests\Stubs\CustomJobDecorator;
use Lorisleiva\Actions\Tests\Stubs\CustomUniqueJobDecorator;
use Lorisleiva\Actions\Tests\TestCase;

uses(TestCase::class)
    ->afterEach(function () {
        // Reset any custom job classes so they don't pollute other tests.
        ActionManager::useJobDecorator(JobDecorator::class);
        ActionManager::useUniqueJobDecorator(UniqueJobDecorator::class);
    })
    ->in(__DIR__);

dataset('custom job decorators', [
    'default job decorator class' => [JobDecorator::class],
    'custom job decorator class' => function () {
        ActionManager::useJobDecorator(CustomJobDecorator::class);

        return CustomJobDecorator::class;
    },
]);

/** @param class-string $class */
function getIdeHelperActionInfo(string $class): ActionInfo
{
    $actionInfos = collect(ActionInfoFactory::create(__DIR__ . '/IdeHelper/stubs'));

    return $actionInfos->filter(fn(ActionInfo $ai) => $ai->fqsen == $class)->firstOrFail();
}

dataset('custom unique job decorators', [
    'default job decorator class' => [UniqueJobDecorator::class],
    'custom job decorator class' => function () {
        ActionManager::useUniqueJobDecorator(CustomUniqueJobDecorator::class);

        return CustomUniqueJobDecorator::class;
    },
]);
