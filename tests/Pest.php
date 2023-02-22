<?php

use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Decorators\JobDecorator;
use Lorisleiva\Actions\Decorators\UniqueJobDecorator;
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

dataset('custom unique job decorators', [
    'default job decorator class' => [UniqueJobDecorator::class],
    'custom job decorator class' => function () {
        ActionManager::useUniqueJobDecorator(CustomUniqueJobDecorator::class);

        return CustomUniqueJobDecorator::class;
    },
]);
