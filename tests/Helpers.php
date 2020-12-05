<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;

function assertJobPushed(string $class, ?Closure $callback = null)
{
    Queue::assertPushed(JobDecorator::class, function (JobDecorator $job) use ($class, $callback) {
        if (! $job->getAction() instanceof $class) {
            return false;
        }

        return $callback ? $callback($job) : true;
    });
}

function assertJobPushedWith(string $class, array $parameters = [])
{
    assertJobPushed($class, function (JobDecorator $job) use ($class, $parameters) {
        return $job->getParameters() === $parameters;
    });
}
