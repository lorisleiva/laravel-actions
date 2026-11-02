<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\CountCrashesAsExceptions;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\SyncQueue;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

#[Backoff(10, 20)]
#[Connection('my_connection')]
#[CountCrashesAsExceptions]
#[Delay(30)]
#[DeleteWhenMissingModels]
#[FailOnTimeout]
#[MaxExceptions(3)]
#[QueueAttribute('my_queue')]
#[Timeout(120)]
#[Tries(10)]
class AsJobConfiguredWithAttributesTest
{
    use AsJob;

    public function handle(): void
    {
        //
    }
}

class AsJobConfiguredWithAttributesAndPropertiesTest extends AsJobConfiguredWithAttributesTest
{
    public string $jobQueue = 'property_queue';
    public int $jobTries = 5;
    public int $jobBackoff = 60;
}

class AsJobConfiguredWithNullPropertyTest
{
    use AsJob;

    public ?int $jobTries = null;
    public int $jobTimeout = 60;

    public function handle(): void
    {
        //
    }
}

#[Timeout(120)]
#[Tries(5)]
class AsJobConfiguredWithAttributesOverParentPropertiesTest extends AsJobConfiguredWithNullPropertyTest
{
}

#[DebounceFor(30)]
#[Delay(5)]
class AsJobConfiguredWithDebounceAndDelayAttributesTest
{
    use AsJob;

    public function handle(): void
    {
        //
    }
}

/**
 * @property int $id
 */
class AsJobConfiguredWithAttributesUser extends Model
{
    public function friends(): HasMany
    {
        return $this->hasMany(self::class);
    }
}

#[UniqueFor(60)]
class AsJobConfiguredWithUniqueForAttributeTest implements ShouldBeUnique
{
    use AsJob;

    public function handle(): void
    {
        //
    }
}

#[WithoutRelations]
class AsJobConfiguredWithWithoutRelationsAttributeTest
{
    use AsJob;

    public function handle(AsJobConfiguredWithAttributesUser $user): void
    {
        //
    }
}

class AsJobConfiguredWithoutAttributesTest
{
    use AsJob;

    public function handle(AsJobConfiguredWithAttributesUser $user): void
    {
        //
    }
}

function payloadForJob(JobDecorator $job): array
{
    $queue = new class extends SyncQueue {
        public function payloadFor(object $job): array
        {
            return json_decode($this->createPayload($job, 'default'), true);
        }
    };

    $queue->setContainer(app());

    return $queue->payloadFor($job);
}

function userWithLoadedRelation(): AsJobConfiguredWithAttributesUser
{
    $user = new AsJobConfiguredWithAttributesUser();
    $user->id = 1;
    $user->setRelation('friends', new Collection());

    return $user;
}

/**
 * @return array<int, string>
 */
function serializedRelationsOf(JobDecorator $job): array
{
    serialize($job);

    /** @var ModelIdentifier $user */
    $user = $job->getParameters()[0];

    return $user->relations;
}

beforeEach(function () {
    // Queue attributes are only available on Laravel 13.
    if (! class_exists(Tries::class)) {
        test()->markTestSkipped('Queue attributes require Laravel 13.');
    }
});

it('uses the configuration provided in attributes', function () {
    // Given we mock the queue driver.
    Queue::fake();

    // When we dispatch the configured job.
    AsJobConfiguredWithAttributesTest::dispatch();

    // Then it was pushed to the queue using the given configurations.
    assertJobPushed(AsJobConfiguredWithAttributesTest::class, function (JobDecorator $job) {
        return $job->connection === 'my_connection'
            && $job->queue === 'my_queue'
            && $job->delay === 30
            && $job->tries === 10
            && $job->maxExceptions === 3
            && $job->backoff() === [10, 20]
            && $job->timeout === 120
            && $job->failOnTimeout === true
            && $job->countCrashesAsExceptions === true
            && $job->deleteWhenMissingModels === true;
    });
});

it('passes the attribute configuration on to the queue payload', function () {
    // When we build the queue payload of the configured job.
    $payload = payloadForJob(AsJobConfiguredWithAttributesTest::makeJob());

    // Then the queue worker receives the configuration from the attributes.
    expect($payload)->toMatchArray([
        'maxTries' => 10,
        'maxExceptions' => 3,
        'backoff' => '10,20',
        'timeout' => 120,
        'failOnTimeout' => true,
        'countCrashesAsExceptions' => true,
        'deleteWhenMissingModels' => true,
    ]);
});

it('lets properties take precedence over inherited attributes', function () {
    // When we make a job from an action that inherits the attributes and overrides some with properties.
    $job = AsJobConfiguredWithAttributesAndPropertiesTest::makeJob();

    // Then the properties win.
    expect($job->queue)->toBe('property_queue');
    expect($job->tries)->toBe(5);
    expect($job->backoff())->toBe(60);

    // And the remaining attributes are still inherited from the parent class.
    expect($job->connection)->toBe('my_connection');
    expect($job->timeout)->toBe(120);
});

it('lets attributes take precedence over null or inherited properties, as Laravel does', function () {
    // When we make a job from an action whose attributes sit on a child of the class declaring the properties.
    $job = AsJobConfiguredWithAttributesOverParentPropertiesTest::makeJob();

    // Then the attributes win over a null property and an inherited default.
    expect($job->tries)->toBe(5);
    expect($job->timeout)->toBe(120);
});

it('ignores the Delay attribute on debounced jobs, as Laravel does', function () {
    // Debounced jobs are only available on Laravel 13.6+.
    if (! class_exists(DebounceFor::class)) {
        test()->markTestSkipped('Debounced jobs require Laravel 13.6+.');
    }

    // Given we mock the queue driver.
    Queue::fake();

    // When we dispatch a debounced action that also has a Delay attribute.
    AsJobConfiguredWithDebounceAndDelayAttributesTest::dispatch();

    // Then it is delayed by the debounce window.
    assertJobPushed(AsJobConfiguredWithDebounceAndDelayAttributesTest::class, function (JobDecorator $job) {
        return $job->delay === 30;
    });
});

it('uses the UniqueFor attribute on unique jobs', function () {
    // When we make a job from a unique action with the UniqueFor attribute.
    $job = AsJobConfiguredWithUniqueForAttributeTest::makeUniqueJob();

    // Then the job is unique for the given amount of seconds.
    expect($job->uniqueFor)->toBe(60);
});

it('serializes models without their relations when the action has the WithoutRelations attribute', function () {
    // When we serialize a job whose action has the WithoutRelations attribute.
    $job = AsJobConfiguredWithWithoutRelationsAttributeTest::makeJob(userWithLoadedRelation());

    // Then the model is serialized without its loaded relations.
    expect(serializedRelationsOf($job))->toBe([]);
});

it('serializes models with their relations by default', function () {
    // When we serialize a job whose action has no WithoutRelations attribute.
    $job = AsJobConfiguredWithoutAttributesTest::makeJob(userWithLoadedRelation());

    // Then the model is serialized with its loaded relations.
    expect(serializedRelationsOf($job))->toBe(['friends']);
});
