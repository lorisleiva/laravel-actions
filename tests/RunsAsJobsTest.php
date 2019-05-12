<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorShouldQueue;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithValidation;

class RunsAsJobsTest extends TestCase
{
    /** @test */
    public function actions_can_be_used_as_dispatchable_jobs()
    {
        $result = SimpleCalculator::dispatchNow([
            'operation' => 'addition',
            'left' => 3,
            'right' => 2,
        ]);

        $this->assertEquals(5, $result);
    }

    /** @test */
    public function actions_can_run_as_queueable_jobs()
    {
        Queue::after(function ($event) {
            $action = unserialize(array_get($event->job->payload(), 'data.command'));
            $this->assertEquals('substraction', $action->operation);
            $this->assertEquals(3, $action->left);
            $this->assertEquals(2, $action->right);
        });

        SimpleCalculatorShouldQueue::dispatch([
            'operation' => 'substraction',
            'left' => 3,
            'right' => 2,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_when_the_action_is_authorized()
    {
        $this->expectException(AuthorizationException::class);

        SimpleCalculatorWithValidation::dispatchNow(['operation' => 'unauthorized']);
    }

    /** @test */
    public function it_throws_an_exception_when_the_action_is_not_validated()
    {
        $this->expectException(ValidationException::class);

        SimpleCalculatorWithValidation::dispatchNow(['operation' => 'invalid']);
    }

    /** @test */
    public function it_keeps_track_of_how_the_action_was_ran()
    {
        $action = new SimpleCalculator(['operation' => 'addition']);

        $action->runAsJob();

        $this->assertTrue($action->runningAs('job'));
    }
}