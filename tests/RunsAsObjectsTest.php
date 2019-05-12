<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class RunsAsObjectsTest extends TestCase
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

        $this->assertEquals('addition', $action->operation);
        $this->assertEquals(3, $action->left);
        $this->assertEquals(5, $action->right);
        $this->assertEquals('This variable is not used in the action.', $action->foo);
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
        $action->set('titi', 'toto');

        $this->assertEquals('addition', $action->operation);
        $this->assertEquals(3, $action->left);
        $this->assertEquals(7, $action->right);
        $this->assertEquals('bar', $action->foo);
        $this->assertEquals('toto', $action->get('titi'));
        $this->assertTrue($action->has('titi'));
        $this->assertFalse($action->has('missing_attribute'));
        $this->assertEquals(10, $action->run());
    }

    /** @test */
    public function it_can_fill_multiple_attributes_at_once()
    {
        $action = (new SimpleCalculator())->fill([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals('addition', $action->operation);
        $this->assertEquals(3, $action->left);
        $this->assertEquals(5, $action->right);
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

    /** @test */
    public function it_can_override_all_attributes_with_a_given_array()
    {
        $action = new SimpleCalculator(['operation' => 'addition', 'foo' => 'bar']);

        $action->setRawAttributes(['operation' => 'substraction']);

        $this->assertEquals(['operation' => 'substraction'], $action->all());
    }

    /** @test */
    public function it_returns_all_attributes_from_an_action()
    {
        $action = new SimpleCalculator([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ], $action->all());
    }

    /** @test */
    public function it_returns_a_subset_of_the_attributes_of_an_action()
    {
        $action = new SimpleCalculator([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals(
            ['operation' => 'addition'], 
            $action->only('operation')
        );

        $this->assertEquals(
            ['right' => 5], 
            $action->except('operation', 'left')
        );
    }
}