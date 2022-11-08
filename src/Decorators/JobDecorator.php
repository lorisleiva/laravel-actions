<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Lorisleiva\Actions\Concerns\DecorateActions;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

class JobDecorator implements ShouldQueue
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

    protected string $actionClass;
    protected array $parameters = [];

    public function __construct(string $action, ...$parameters)
    {
        $this->actionClass = $action;
        $this->setAction(app($action));
        $this->parameters = $parameters;
        $this->constructed();
    }

    protected function constructed()
    {
        $this->onConnection($this->fromActionProperty('jobConnection'));
        $this->onQueue($this->fromActionProperty('jobQueue'));
        $this->setTries($this->fromActionProperty('jobTries'));
        $this->setMaxExceptions($this->fromActionProperty('jobMaxExceptions'));
        $this->setTimeout($this->fromActionProperty('jobTimeout'));
        $this->fromActionMethod('configureJob', [$this]);
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

    /**
     * @param int|null $tries
     * @return $this
     */
    public function setTries(?int $tries)
    {
        $this->tries = $tries;

        return $this;
    }

    /**
     * @param int|null $maxException
     * @return $this
     */
    public function setMaxExceptions(?int $maxException)
    {
        $this->maxExceptions = $maxException;

        return $this;
    }

    /**
     * @param int|null $timeout
     * @return $this
     */
    public function setTimeout(?int $timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function decorates(string $actionClass): bool
    {
        return $this->getAction() instanceof $actionClass;
    }

    public function backoff()
    {
        return $this->fromActionMethodOrProperty(
            'getJobBackoff',
            'jobBackoff',
            null,
            $this->parameters
        );
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
     * Laravel will call failed() on a job that fails. This function will call
     * the function jobFailed(Throwable $e) on the underlying action if Laravel
     * calls the failed() function on the job.
     *
     * @param Throwable $e
     * @return void
     */
    public function failed(Throwable $e)
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

        /** @var ReflectionParameter $firstParameter */
        if (! $firstParameter = Arr::first($reflectionMethod->getParameters())) {
            return $this->parameters;
        }

        $firstParameterClass = Reflector::getParameterClassName($firstParameter);

        if ($firstParameter->allowsNull() && $firstParameterClass === Batch::class) {
            return [$this->batch(), ...$this->parameters];
        } elseif (is_subclass_of($firstParameterClass, self::class) || $firstParameterClass === self::class) {
            return [$this, ...$this->parameters];
        } else {
            return $this->parameters;
        }
    }

    protected function serializeProperties()
    {
        $this->action = $this->actionClass;

        array_walk($this->parameters, function (&$value) {
            $value = $this->getSerializedPropertyValue($value);
        });
    }

    protected function unserializeProperties()
    {
        $this->setAction(app($this->actionClass));

        array_walk($this->parameters, function (&$value) {
            $value = $this->getRestoredPropertyValue($value);
        });
    }

    public function __serialize()
    {
        $this->serializeProperties();

        return $this->serializeFromSerializesModels();
    }

    public function __unserialize(array $values)
    {
        $this->unserializeFromSerializesModels($values);
        $this->unserializeProperties();
    }
}
