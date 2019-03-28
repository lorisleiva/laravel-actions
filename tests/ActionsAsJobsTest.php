<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithValidation;

class ActionsAsJobsTest extends TestCase
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

    /** @todo */
    public function it_throws_an_exception_when_the_action_is_authorized()
    {
        //
    }

    /** @todo */
    public function it_throws_an_exception_when_the_action_is_not_validated()
    {
        //
    }
}