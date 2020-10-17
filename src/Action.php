<?php

namespace Lorisleiva\Actions;

use BadMethodCallException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * @method mixed run(...$mixed)
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

    protected $actingAs;
    protected $runningAs = 'object';
    protected $getAttributesFromConstructor = false;

    /**
     * Register the action as a command if a signature is defined
     * and register the action's routes if they are present.
     */
    public static function register(): void
    {
        static::registerCommand();
        static::routes(app(Router::class));

        if (method_exists(static::class, 'registered')) {
            static::registered();
        }
    }

    public function __construct()
    {
        if (method_exists($this, 'initialized')) {
            $this->resolveAndCall($this, 'initialized');
        }

        $args = func_num_args() > 0 ? func_get_args() : [];
        $this->resolveAttributesFromConstructor(...$args);
    }

    protected function resolveAttributesFromConstructor(...$arguments)
    {
        if (method_exists($this, 'getAttributesFromConstructor')) {
            return $this->fill(call_user_func_array([$this, 'getAttributesFromConstructor'], $arguments));
        }

        if (! $attributes = $this->getAttributesFromConstructor) {
            return $this->fill(Arr::get($arguments, 0, []));
        }

        if ($attributes === true && method_exists($this, 'handle')) {
            $reflector = new ReflectionMethod($this, 'handle');
            foreach($reflector->getParameters() as $index => $param) {
               $defaultValue = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
               $this->set($param->getName(), Arr::get($arguments, $index, $defaultValue));
            }
            return $this;
        }

        foreach (Arr::wrap($attributes) as $index => $name) {
            $this->set($name, Arr::get($arguments, $index, null));
        }

        return $this;
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
        return (new static)->fill($action->all())->actingAs($action->user());
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

    protected function handleRun(array $attributes = [])
    {
        $this->fill($attributes);
        $this->prepareForValidation();
        $this->resolveBeforeHook();
        $this->resolveAuthorization();
        $this->resolveValidation();

        if (! method_exists($this, 'handle')) {
            return;
        }

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
        if ($method === 'run') {
            return (new static(...$parameters))->handleRun();
        }

        return (new static)->$method(...$parameters);
    }
}
