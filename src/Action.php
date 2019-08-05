<?php

namespace Lorisleiva\Actions;

use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class Action
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Concerns\SerializesModels;
    use Concerns\HasAttributes;
    use Concerns\ResolvesMethodDependencies;
    use Concerns\ResolvesAuthorization;
    use Concerns\ResolvesValidation;
    use Concerns\RunsAsController;
    use Concerns\RunsAsListener;
    use Concerns\RunsAsJob;

    protected $actingAs;
    protected $runningAs = 'object';

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);

        if (method_exists($this, 'register')) {
            $this->register();
        }
    }

    public static function createFrom(Action $action)
    {
        return (new static)->fill($action->all());
    }

    public function runAs(Action $action)
    {
        if ($action->runningAs('job')) {
            return $this->runAsJob();
        }
        
        if ($action->runningAs('listener')) {
            return $this->runAsListener();
        }
        
        if ($action->runningAs('controller')) {
            return $this->runAsController($action->getRequest());
        }

        return $this->run();
    }

    public function run(array $attributes = [])
    {
        $this->fill($attributes);
        $this->resolveBeforeHook();
        $this->resolveAuthorization();
        $this->resolveValidation();

        return $this->resolveAndCall($this, 'handle');
    }

    public function resolveBeforeHook()
    {
        $method = 'as' . Str::studly($this->runningAs);

        if (method_exists($this, $method)) {
            return $this->resolveAndCall($this, $method);
        }
    }

    public function runningAs($matches)
    {
        return in_array($this->runningAs, is_array($matches) ? $matches : func_get_args());
    }

    public function actingAs($user)
    {
        $this->actingAs = $user;

        return $this;
    }

    public function user()
    {
        return $this->actingAs ?? Auth::user();
    }

    public function reset($user = null)
    {
        $this->actingAs = $user;
        $this->attributes = [];
        $this->validator = null;
    }

    public function delegateTo($actionClass)
    {
        return $actionClass::createFrom($this)->runAs($this);
    }
    
    public function __invoke(array $attributes = [])
    {
        return $this->run($attributes);
    }

    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
