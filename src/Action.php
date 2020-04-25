<?php

namespace Lorisleiva\Actions;

use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * @method mixed run(array $attributes = [])
 * @method static mixed run(array $attributes = [])
 */
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
    use Concerns\RunsAsCommand;

    protected static $booted = false;
    protected $actingAs;
    protected $runningAs = 'object';

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);

        if (! static::$booted) {
            static::boot();
        }

        if (method_exists($this, 'initialized')) {
            $this->resolveAndCall($this, 'initialized');
        }
    }

    public static function boot()
    {
        static::$booted = true;

        if (method_exists(static::class, 'booted')) {
            static::booted();
        }
    }

    /**
     * @param array $attributes
     * @return static
     */
    public static function make(array $attributes = [])
    {
        return new static($attributes);
    }

    /**
     * @param Action $action
     * @return static
     */
    public static function createFrom(Action $action)
    {
        return (new static)->fill($action->all());
    }

    /**
     * @param string $actionClass
     * @return mixed
     * @uses createFrom
     * @uses runAs
     */
    public function delegateTo(string $actionClass)
    {
        return $actionClass::createFrom($this)->runAs($this);
    }

    /**
     * @param Action $action
     * @return mixed
     */
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

        if ($action->runningAs('command')) {
            return $this->runAsCommand($action->getCommandInstance());
        }

        return $this->run();
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    protected function handleRun(array $attributes = [])
    {
        $this->fill($attributes);
        $this->prepareForValidation();
        $this->resolveBeforeHook();
        $this->resolveAuthorization();
        $this->resolveValidation();

        return $this->resolveAndCall($this, 'handle');
    }

    protected function prepareForValidation()
    {
        //
    }

    protected function resolveBeforeHook()
    {
        $method = 'as' . Str::studly($this->runningAs);
        $extras = [];

        if (! method_exists($this, $method)) {
            return null;
        }

        if ($this->runningAs('command')) {
            $extras['command'] = $this->getCommandInstance();
        }

        return $this->resolveAndCall($this, $method, $extras);
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

    public function __invoke(array $attributes = [])
    {
        return $this->run($attributes);
    }

    public function __call($method, $parameters)
    {
        if ($method === 'run') {
            return $this->handleRun(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}
