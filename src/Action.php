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

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function run()
    {
        $this->resolveAuthorization();
        $this->resolveValidation();
        
        return $this->resolveHandle();
    }

    public function resolveHandle()
    {
        $parameters = $this->resolveMethodDependencies($this, 'handle');

        return $this->handle(...$parameters);
    }
}