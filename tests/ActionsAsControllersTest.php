<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class ActionsAsControllersTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->make('router')->post('/calculator/{operation}', SimpleCalculator::class);
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
}