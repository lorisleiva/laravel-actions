<?php

namespace Lorisleiva\Actions\Tests;

trait DiscoversListeners
{
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('actions.listeners.discovery.enabled', true);
        $app['config']->set('actions.listeners.discovery.paths', [__DIR__.'/Stubs']);
    }
}
