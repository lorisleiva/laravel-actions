<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobSerializedTest
{
    use AsJob;

    public function handle()
    {
        //
    }
}

it('serialises the action into its classname', function () {
    // When we serialise the JobDecorator.
    $job = AsJobSerializedTest::makeJob();
    $serializedJob = parseSerializedData(serialize($job));

    // Then the decorated action has been serialised by using its FQN only.
    expect($serializedJob['action'])->toBe(AsJobSerializedTest::class);
});

it('unserialises the action from the container', function () {
    // Given we have a JobDecorator.
    $job = AsJobSerializedTest::makeJob();

    // And a after resolving listener on the action.
    $resolved = false;
    app()->afterResolving(AsJobSerializedTest::class, function () use (&$resolved) {
        $resolved = true;
    });

    // When we serialise and unserialise the JobDecorator.
    $unserializedJob = unserialize(serialize($job));

    // Then the decorated action has been resolved from the container.
    expect($unserializedJob)->toBeInstanceOf(JobDecorator::class);
    expect($unserializedJob->getAction())->toBeInstanceOf(AsJobSerializedTest::class);
    expect($resolved)->toBeTrue();
});

it('serialises Eloquent models within the parameters', function () {
    // Given an persisted model.
    loadMigrations();
    $model = createUser();
});

it('unserialises Eloquent models within the parameters', function () {
    // Given an persisted model.
    loadMigrations();
    $model = createUser();
});

it('serialises Eloquent collections within the parameters', function () {
    // Given an persisted collection of models.
    loadMigrations();
    $modelA = createUser();
    $modelB = createUser();
    $collection = Collection::make([$modelA, $modelB]);
});

it('unserialises Eloquent collections within the parameters', function () {
    // Given an persisted collection of models.
    loadMigrations();
    $modelA = createUser();
    $modelB = createUser();
    $collection = Collection::make([$modelA, $modelB]);
});
