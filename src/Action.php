<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Routing\Controller;
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

    protected $runningAs = 'object';

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);

        if (method_exists($this, 'register')) {
            $this->register();
        }
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
        $any = is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args();

        return in_array($this->runningAs, $any);
    }
}