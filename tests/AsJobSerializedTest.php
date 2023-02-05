<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Database\ModelIdentifier;
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
    // When we serialise a JobDecorator.
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

    // When we serialise a JobDecorator that has a model as a parameter.
    $job = AsJobSerializedTest::makeJob($model);
    $serializedJob = parseSerializedData(serialize($job));

    // Then the model parameter has been serialised into a ModelIdentifier.
    $firstParameter = (array) data_get($serializedJob, 'parameters.0');
    expect($firstParameter['__PHP_Incomplete_Class_Name'])->toBe(ModelIdentifier::class)
        ->and($firstParameter['class'])->toBe(get_class($model))
        ->and($firstParameter['id'])->toBe($model->id)
        ->and($firstParameter['relations'])->toBe([])
        ->and($firstParameter['connection'])->toBe("testing");
});

it('unserialises Eloquent models within the parameters', function () {
    // Given an persisted model.
    loadMigrations();
    $model = createUser();

    // When we serialise and unserialise a JobDecorator that has a model as a parameter.
    $job = AsJobSerializedTest::makeJob($model);
    $unserializedJob = unserialize(serialize($job));

    // Then the model parameter has been unserialised to a new model instance.
    $firstParameter = $unserializedJob->getParameters()[0];
    expect($firstParameter)->toBeInstanceOf(get_class($model));
    expect($firstParameter->id)->toBe($model->id);
});

it('serialises Eloquent collections within the parameters', function () {
    // Given an persisted collection of models.
    loadMigrations();
    $modelA = createUser();
    $modelB = createUser();
    $collection = Collection::make([$modelA, $modelB]);

    // When we serialise a JobDecorator that has a collection of models as a parameter.
    $job = AsJobSerializedTest::makeJob($collection);
    $serializedJob = parseSerializedData(serialize($job));

    // Then the collection parameter has been serialised into a ModelIdentifier.
    $firstParameter = (array) data_get($serializedJob, 'parameters.0');
    expect($firstParameter['__PHP_Incomplete_Class_Name'])->toBe(ModelIdentifier::class)
        ->and($firstParameter['class'])->toBe(get_class($modelA))
        ->and($firstParameter['id'])->toBe($collection->pluck('id')->toArray())
        ->and($firstParameter['relations'])->toBe([])
        ->and($firstParameter['connection'])->toBe("testing");
});

it('unserialises Eloquent collections within the parameters', function () {
    // Given an persisted collection of models.
    loadMigrations();
    $modelA = createUser();
    $modelB = createUser();
    $collection = Collection::make([$modelA, $modelB]);

    // When we serialise and unserialise a JobDecorator that has a collection of models as a parameter.
    $job = AsJobSerializedTest::makeJob($collection);
    $unserializedJob = unserialize(serialize($job));

    // Then the collection parameter has been unserialised to a new collection of models.
    $firstParameter = $unserializedJob->getParameters()[0];
    expect($firstParameter)->toBeInstanceOf(Collection::class);
    expect($firstParameter->get(0))->toBeInstanceOf(get_class($modelA));
    expect($firstParameter->get(0)->id)->toBe($modelA->id);
    expect($firstParameter->get(1))->toBeInstanceOf(get_class($modelB));
    expect($firstParameter->get(1)->id)->toBe($modelB->id);
});
