<?php

namespace Lorisleiva\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Routing\Controller;

abstract class Action extends Controller
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
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