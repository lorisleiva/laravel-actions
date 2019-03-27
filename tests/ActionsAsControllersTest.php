<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithValidation;

class ActionsAsControllersTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->make('router')->post('/calculator/{operation}', SimpleCalculator::class);
        $app->make('router')->post('/calculator/validated/{operation}', SimpleCalculatorWithValidation::class);
    }

    /** @test */
    public function actions_can_be_used_as_invokable_controllers()
    {
        $payload = [
            'left' => 3,
            'right' => 5,
        ];

        $this->post('/calculator/addition', $payload)
            ->assertOk()
            ->assertSee('(addition)')
            ->assertSee('Left: 3')
            ->assertSee('Right: 5')
            ->assertSee('Result: 8');
    }

    /** @test */
    public function it_returns_a_403_when_the_action_is_authorized()
    {
        $this->post('/calculator/validated/unauthorized')->assertForbidden();
    }

    /** @test */
    public function it_redirects_back_when_the_action_is_not_validated()
    {
        $this->post('/calculator/validated/invalid')->assertRedirect();
    }
}