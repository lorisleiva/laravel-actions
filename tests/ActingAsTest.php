<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Illuminate\Http\Request;

class ActingAsTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->loadLaravelMigrations();
    }

    /** @test */
    public function it_keeps_track_of_the_authenticated_user()
    {
        $this->actingAs($user = $this->createUser());

        $action = new SimpleCalculator;

        $this->assertEquals($user->id, $action->user()->id);
    }

    /** @test */
    public function it_returns_null_when_unauthenticated()
    {
        $action = new SimpleCalculator;

        $this->assertNull($action->user());
    }

    /** @test */
    public function it_can_act_on_behalf_of_another_user()
    {
        $this->actingAs($userA = $this->createUser());

        $action = new SimpleCalculator;
        $action->actingAs($userB = $this->createUser());

        $this->assertEquals($userB->id, $action->user()->id);
    }

    /** @test */
    public function it_takes_the_user_from_the_request_when_ran_as_controller()
    {
        $request = (new Request)
            ->merge(['operation' => 'addition'])
            ->setUserResolver(function () {
                return $this->createUser(['name' => 'Alice From Request']);
            });

        $action = new SimpleCalculator;
        $action->runAsController($request);

        $this->assertEquals('Alice From Request', $action->user()->name);
    }
}