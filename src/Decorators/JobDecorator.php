<?php

namespace Lorisleiva\Actions\Decorators;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lorisleiva\Actions\Concerns\DecorateActions;
use Mockery\MockInterface;

class JobDecorator implements ShouldQueue
{
    use DecorateActions;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels {
        __sleep as protected sleepFromSerializesModels;
        __wakeup as protected wakeupFromSerializesModels;
        __serialize as protected serializeFromSerializesModels;
        __unserialize as protected unserializeFromSerializesModels;
    }

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
        if ($this->hasMethod('configureJob')) {
            $this->callMethod('configureJob', [$this]);
        }
    }

    public function handle()
    {
        if ($this->hasMethod('asJob')) {
            return $this->callMethod('asJob', $this->parameters);
        }

        if ($this->hasMethod('handle')) {
            return $this->callMethod('handle', $this->parameters);
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

    public function middleware()
    {
        return $this->hasMethod('getJobMiddleware')
            ? $this->callMethod('getJobMiddleware', $this->parameters)
            : [];
    }

    public function displayName(): string
    {
        return $this->hasMethod('getJobDisplayName')
            ? $this->callMethod('getJobDisplayName', $this->parameters)
            : get_class($this->action);
    }

    public function tags()
    {
        return $this->hasMethod('getJobTags')
            ? $this->callMethod('getJobTags', $this->parameters)
            : [];
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

    public function __sleep()
    {
        $this->serializeProperties();

        return $this->sleepFromSerializesModels();
    }

    public function __wakeup()
    {
        $this->wakeupFromSerializesModels();
        $this->unserializeProperties();
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
