<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class ActionsAsObjectsTest extends TestCase
{
    /** @test */
    public function it_runs_actions_as_simple_objects()
    {
        $action = new SimpleCalculator([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals(8, $action->run());
    }

    /** @test */
    public function it_provides_all_given_attributes_as_public_properties_on_the_action()
    {
        $action = new SimpleCalculator([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
            'foo' => 'This variable is not used in the action.',
        ]);

        $this->assertEquals($action->operation, 'addition');
        $this->assertEquals($action->left, 3);
        $this->assertEquals($action->right, 5);
        $this->assertEquals($action->foo, 'This variable is not used in the action.');
    }

    /** @test */
    public function it_can_update_attributes_or_set_up_new_ones()
    {
        $action = new SimpleCalculator([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $action->right = 7;
        $action->foo = 'bar';

        $this->assertEquals($action->operation, 'addition');
        $this->assertEquals($action->left, 3);
        $this->assertEquals($action->right, 7);
        $this->assertEquals($action->foo, 'bar');
        $this->assertEquals($action->run(), 10);
    }

    /** @test */
    public function it_can_fill_multiple_attributes_at_once()
    {
        $action = (new SimpleCalculator())->fill([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals($action->operation, 'addition');
        $this->assertEquals($action->left, 3);
        $this->assertEquals($action->right, 5);
    }

    /** @test */
    public function it_can_run_with_some_additional_data_to_fill()
    {
        $response = (new SimpleCalculator())->run([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals(8, $response);
    }
}