<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class Action extends Controller
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

    public function run(array $attributes = [])
    {
        $this->fill($attributes);
        $this->resolveAuthorization();
        $this->resolveValidation();

        return $this->resolveHandle();
    }

    public function resolveHandle()
    {
        $parameters = $this->resolveMethodDependencies($this, 'handle');

        return $this->handle(...$parameters);
    }

    public function runningAs()
    {
        $matches = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();

        return in_array($this->runningAs, $matches);
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
}