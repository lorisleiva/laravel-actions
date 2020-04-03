<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithCommandSignature;
use Symfony\Component\Console\Input\ArrayInput;

class RunningAsTest extends TestCase
{
    /** @test */
    public function it_keeps_track_of_how_actions_ran_as_objects()
    {
        $action = new SimpleCalculator();

        $this->assertTrue($action->runningAs('object'));
    }

    /** @test */
    public function it_keeps_track_of_how_actions_ran_as_controllers()
    {
        $action = new SimpleCalculator;
        $request = (new Request)->merge(['operation' => 'addition']);

        $action->runAsController($request);

        $this->assertTrue($action->runningAs('controller'));
    }

    /** @test */
    public function it_keeps_track_of_how_actions_ran_as_listeners()
    {
        $action = new SimpleCalculator(['operation' => 'addition']);

        $action->runAsListener();

        $this->assertTrue($action->runningAs('listener'));
    }

    /** @test */
    public function it_keeps_track_of_how_actions_ran_as_jobs()
    {
        $action = new SimpleCalculator(['operation' => 'addition']);

        $action->runAsJob();

        $this->assertTrue($action->runningAs('job'));
    }

    /** @test */
    public function it_keeps_track_of_how_actions_ran_as_commands()
    {
        $action = new SimpleCalculatorWithCommandSignature();
        $input = new ArrayInput(['operation' => 'addition',
            'left' => '2',
            'right' => '3'], $action->getInputDefinition());
        $action->runAsCommand($input);

        $this->assertTrue($action->runningAs('command'));
    }
}
