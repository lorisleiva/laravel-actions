<?php

namespace Lorisleiva\Actions;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Reflector;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Lody\Lody;

class EventServiceProvider extends ServiceProvider
{

    public function shouldDiscoverEvents()
    {
        return config('actions.listeners.discovery.enabled');
    }

    public function discoverEvents(): array
    {
        $listeners = $this->discoverListeners();

        $discoveredEvents = [];

        foreach ($listeners as $listener => $events) {
            foreach ($events as $event) {
                if (! isset($discoveredEvents[$event])) {
                    $discoveredEvents[$event] = [];
                }

                $discoveredEvents[$event][] = $listener;
            }
        }

        return $discoveredEvents;

    }

    protected function discoverListeners(): array
    {
        return Lody::classes($this->discoverEventsWithin())
                   ->hasTrait(AsListener::class)
                   ->filter(function ($class): bool {
                       return method_exists($class, 'handle');
                   })
                   ->mapWithKeys(function ($class) {
                       $method = (method_exists($class, 'asListener'))
                           ? new \ReflectionMethod($class, 'asListener')
                           : new \ReflectionMethod($class, 'handle');

                       return [
                           $class.'@handle' =>
                               Reflector::getParameterClassNames($method->getParameters()[0])
                       ];
                   })
                   ->filter()
                   ->toArray();
    }

    protected function discoverEventsWithin()
    {
        return config('actions.listeners.discovery.paths') ?? [
            $this->app->path('Actions'),
        ];
    }


}
