<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\PreparesForDispatch;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\CountCrashesAsExceptions;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Illuminate\Support\Traits\ReadsClassAttributes;
use Lorisleiva\Actions\Concerns\DecorateActions;
use ReflectionMethod;
use Throwable;

class JobDecorator implements ShouldQueue, PreparesForDispatchWhenSupported
{
    use DecorateActions;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;
    use SerializesModels {
        __serialize as protected serializeFromSerializesModels;
        __unserialize as protected unserializeFromSerializesModels;
    }

    public ?int $tries;
    public ?int $maxExceptions;
    public ?int $timeout;
    public ?bool $deleteWhenMissingModels;
    public bool $failOnTimeout = false;
    public bool $countCrashesAsExceptions = false;
    public bool $shouldBeEncrypted = false;

    protected static ?ActionAttributes $actionAttributes = null;

    protected string $actionClass;
    protected array $parameters = [];

    public function __construct(string $action, ...$parameters)
    {
        $this->actionClass = $action;
        $this->setAction(app($action));
        $this->parameters = $parameters;
        $this->constructed();
    }

    protected function constructed(): void
    {
        $this->onConnection($this->fromActionPropertyOrAttribute('jobConnection', Connection::class));
        $this->onQueue($this->fromActionPropertyOrAttribute('jobQueue', QueueAttribute::class));
        $this->delay($this->delayFromAction());
        $this->setTries($this->fromActionPropertyOrAttribute('jobTries', Tries::class));
        $this->setMaxExceptions($this->fromActionPropertyOrAttribute('jobMaxExceptions', MaxExceptions::class));
        $this->setTimeout($this->fromActionPropertyOrAttribute('jobTimeout', Timeout::class));
        $this->setFailOnTimeout((bool) $this->fromActionPropertyOrAttribute('jobFailOnTimeout', FailOnTimeout::class));
        $this->setCountCrashesAsExceptions((bool) $this->fromActionPropertyOrAttribute('jobCountCrashesAsExceptions', CountCrashesAsExceptions::class));
        $this->setDeleteWhenMissingModels($this->fromActionMethodPropertyOrAttribute(
            'getJobDeleteWhenMissingModels',
            'jobDeleteWhenMissingModels',
            DeleteWhenMissingModels::class
        ));
        $this->applyQueueInterfacesFromAction();
        $this->fromActionMethod('configureJob', [$this]);
    }

    /**
     * Laravel only checks these interfaces on the queued command, which is this
     * decorator rather than the action. It also honours the matching properties,
     * so we set those instead of implementing the interfaces on every decorator.
     */
    protected function applyQueueInterfacesFromAction(): void
    {
        if ($this->action instanceof ShouldBeEncrypted) {
            $this->shouldBeEncrypted = true;
        }

        if ($this->action instanceof ShouldQueueAfterCommit) {
            $this->afterCommit();
        }
    }

    /**
     * Laravel ignores #[Delay] on debounced jobs, which DebounceJobDecorator
     * mirrors by overriding this.
     */
    protected function delayFromAction(): mixed
    {
        return $this->fromActionAttribute(Delay::class);
    }

    /**
     * Laravel reads queue attributes off the queued command, which is this
     * decorator rather than the action, so we copy them across. Precedence
     * between the property and the attribute is resolved by Laravel itself.
     */
    protected function fromActionPropertyOrAttribute(string $property, string $attributeClass): mixed
    {
        $attributes = static::actionAttributes();

        if (! $attributes) {
            return $this->fromActionProperty($property);
        }

        return $attributes->valueOrProperty($this->action, $attributeClass, $property);
    }

    protected function fromActionMethodPropertyOrAttribute(string $method, string $property, string $attributeClass): mixed
    {
        if ($this->hasMethod($method)) {
            return $this->callMethod($method, $this->parameters);
        }

        return $this->fromActionPropertyOrAttribute($property, $attributeClass);
    }

    protected function fromActionAttribute(string $attributeClass): mixed
    {
        return static::actionAttributes()?->value($this->actionClass, $attributeClass);
    }

    /**
     * @template T of object
     * @param class-string $actionClass
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    protected static function actionAttribute(string $actionClass, string $attributeClass): ?object
    {
        return static::actionAttributes()?->instance($actionClass, $attributeClass);
    }

    /**
     * Queue attributes are read with ReadsClassAttributes, which only exists
     * from Laravel 13. Checking it first keeps ActionAttributes from loading.
     */
    protected static function actionAttributes(): ?ActionAttributes
    {
        if (! trait_exists(ReadsClassAttributes::class)) {
            return null;
        }

        return static::$actionAttributes ??= new ActionAttributes();
    }

    public function handle()
    {
        if ($this->hasMethod('asJob')) {
            return $this->callMethod('asJob', $this->getPrependedParameters('asJob'));
        }

        if ($this->hasMethod('handle')) {
            return $this->callMethod('handle', $this->getPrependedParameters('handle'));
        }
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setTries(?int $tries): self
    {
        $this->tries = $tries;

        return $this;
    }

    public function setMaxExceptions(?int $maxException): self
    {
        $this->maxExceptions = $maxException;

        return $this;
    }

    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setFailOnTimeout(bool $failOnTimeout): self
    {
        $this->failOnTimeout = $failOnTimeout;

        return $this;
    }

    public function setCountCrashesAsExceptions(bool $countCrashesAsExceptions): self
    {
        $this->countCrashesAsExceptions = $countCrashesAsExceptions;

        return $this;
    }

    public function setDeleteWhenMissingModels(?bool $deleteWhenMissingModels): self
    {
        $this->deleteWhenMissingModels = $deleteWhenMissingModels;

        return $this;
    }

    public function decorates(string $actionClass): bool
    {
        return $this->getAction() instanceof $actionClass;
    }

    public function backoff()
    {
        return $this->fromActionMethodPropertyOrAttribute('getJobBackoff', 'jobBackoff', Backoff::class);
    }

    public function retryUntil()
    {
        return $this->fromActionMethodOrProperty(
            'getJobRetryUntil',
            'jobRetryUntil',
            null,
            $this->parameters
        );
    }

    public function middleware()
    {
        return $this->fromActionMethod('getJobMiddleware', $this->parameters, []);
    }

    /**
     * Laravel calls prepareForDispatch() before dispatching the job, and cancels
     * the dispatch when it returns false. Only actions that implement
     * PreparesForDispatch are asked, receiving the dispatched parameters.
     *
     * The action is stateless, so it can't prepare itself the way a native job
     * does. Like asJob(), it can ask for this decorator as its first argument
     * and prepare that instead.
     */
    public function prepareForDispatch()
    {
        if (! $this->action instanceof PreparesForDispatch) {
            return null;
        }

        return $this->callMethod('prepareForDispatch', $this->getPrependedParameters('prepareForDispatch'));
    }

    /**
     * Laravel calls interrupted() when the worker receives a signal while the
     * job runs, but only on decorators that implement Interruptible, which
     * makeJob() picks for actions that implement it.
     */
    public function interrupted(int $signal): void
    {
        $this->fromActionMethod('interrupted', [$signal, ...$this->parameters]);
    }

    /**
     * Laravel will call failed() on a job that fails. This function will call
     * the function jobFailed(Throwable $e) on the underlying action if Laravel
     * calls the failed() function on the job.
     */
    public function failed(Throwable $e): void
    {
        $this->fromActionMethod('jobFailed', [$e, ...$this->parameters], []);
    }

    public function displayName(): string
    {
        return $this->fromActionMethod(
            'getJobDisplayName',
            $this->parameters,
            get_class($this->action)
        );
    }

    public function tags()
    {
        return $this->fromActionMethod('getJobTags', $this->parameters, []);
    }

    protected function getPrependedParameters(string $method): array
    {
        $reflectionMethod = new ReflectionMethod($this->action, $method);
        $numberOfParameters = $reflectionMethod->getNumberOfParameters();

        if (! $reflectionMethod->isVariadic() && $numberOfParameters <= count($this->parameters)) {
            return $this->parameters;
        }

        $firstParameter = Arr::first($reflectionMethod->getParameters());

        if (! $firstParameter) {
            return $this->parameters;
        }

        $firstParameterClass = Reflector::getParameterClassName($firstParameter);

        if ($firstParameter->allowsNull() && $firstParameterClass === Batch::class) {
            return [$this->batch(), ...$this->parameters];
        }
        if (is_subclass_of($firstParameterClass, self::class) || $firstParameterClass === self::class) {
            return [$this, ...$this->parameters];
        }

        return $this->parameters;
    }

    protected function serializeProperties(): void
    {
        $this->action = $this->actionClass;
        $withRelations = static::actionAttribute($this->actionClass, WithoutRelations::class) === null;

        array_walk($this->parameters, function (&$value) use ($withRelations) {
            $value = $this->getSerializedPropertyValue($value, $withRelations);
        });
    }

    protected function unserializeProperties(): void
    {
        $this->setAction(app($this->actionClass));

        array_walk($this->parameters, function (&$value) {
            $value = $this->getRestoredPropertyValue($value);
        });
    }

    public function __serialize(): array
    {
        $this->serializeProperties();

        return $this->serializeFromSerializesModels();
    }

    public function __unserialize(array $values): void
    {
        $this->unserializeFromSerializesModels($values);
        $this->unserializeProperties();
    }
}
