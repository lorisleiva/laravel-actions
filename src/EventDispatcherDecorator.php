<?php

namespace Lorisleiva\Actions;

use Illuminate\Support\Str;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Container\Container as ContainerContract;

class EventDispatcherDecorator implements DispatcherContract
{
    protected $dispatcher;
    protected $container;

    public function __construct(DispatcherContract $dispatcher, ContainerContract $container)
    {
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }

    public function listen($events, $listener)
    {
        if ($this->isActionFullyQualifiedName($listener)) {
            $listener = $listener . '@runAsListener';
        }

        return $this->dispatcher->listen($events, $listener);
    }

    public function isActionFullyQualifiedName($listener)
    {
        if (! is_string($listener)) {
            return false;
        }

        [$class, $method] = Str::parseCallback($listener);

        if (! is_null($method)) {
            return false;
        }

        return in_array(Action::class, class_parents($class));
    }

    public function hasListeners($eventName)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    public function subscribe($subscriber)
    {
        return $this->dispatcher->subscribe($subscriber);
    }

    public function until($event, $payload = [])
    {
        return $this->dispatcher->until($event, $payload);
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        return $this->dispatcher->dispatch($event, $payload, $halt);
    }

    public function push($event, $payload = [])
    {
        return $this->dispatcher->push($event, $payload);
    }

    public function flush($event)
    {
        return $this->dispatcher->flush($event);
    }

    public function forget($event)
    {
        return $this->dispatcher->forget($event);
    }

    public function forgetPushed()
    {
        return $this->dispatcher->forgetPushed();
    }

    public function __call($method, $parameters)
    {
        return $this->dispatcher->{$method}(...$parameters);
    }
}
