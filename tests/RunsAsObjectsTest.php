<?php

namespace Lorisleiva\Actions\Tests;

use BadMethodCallException;
use Lorisleiva\Actions\Action;
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
    public function its_attributes_can_use_isset()
    {
        $action = new SimpleCalculator([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertTrue(isset($action->right));
        $this->assertFalse(isset($action->foo));

        $action->foo = 'bar';

        $this->assertTrue(isset($action->foo));
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
    public function running_mulitple_times_uses_the_latest_attributes()
    {
        $action = new SimpleCalculator();

        $response = $action->run([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals(8, $response);

        $response = $action->run([
            'operation' => 'substraction',
            'left' => 3,
            'right' => 2,
        ]);

        $this->assertEquals(1, $response);
    }

    /** @test */
    public function it_can_run_like_an_invokable_object()
    {
        $response = (new SimpleCalculator())([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ]);

        $this->assertEquals(8, $response);
    }

    /** @test */
    public function it_can_be_instantiated_statically()
    {
        $response = SimpleCalculator::make([
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ])->run();

        $this->assertEquals(8, $response);
    }

    /** @test */
    public function it_can_run_through_the_run_static_method()
    {
        $response = SimpleCalculator::run([
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

    /** @test */
    public function actions_can_have_no_handle_method()
    {
        // An empty action...
        $action = new class() extends Action {};

        // ...returns null without throwing any exceptions.
        $this->assertNull($action->run());
    }

    /** @test */
    public function it_can_override_the_way_we_get_attribute_from_the_constructor()
    {
        $action = new class('addition', 3, 5) extends SimpleCalculator {
            public function getAttributesFromConstructor(string $operation, int $left, int $right): array
            {
                return compact('operation', 'left', 'right');
            }
        };

        $this->assertEquals('addition', $action->operation);
        $this->assertEquals(3, $action->left);
        $this->assertEquals(5, $action->right);
        $this->assertEquals(8, $action->run());
    }

    /** @test */
    public function it_can_override_constructor_attributes_via_a_property()
    {
        $action = new class('addition', 3, 5) extends SimpleCalculator {
            protected $getAttributesFromConstructor = ['operation', 'left', 'right'];
        };

        $this->assertEquals('addition', $action->operation);
        $this->assertEquals(3, $action->left);
        $this->assertEquals(5, $action->right);
        $this->assertEquals(8, $action->run());
    }

    /** @test */
    public function it_can_override_constructor_attributes_by_reflecting_the_properties_of_the_handle_method()
    {
        $action = new class('addition', 3, 5) extends SimpleCalculator {
            protected $getAttributesFromConstructor = true;

            public function handle($operation, $left, $right)
            {
                return parent::handle($operation, $left, $right);
            }
        };

        $this->assertEquals('addition', $action->operation);
        $this->assertEquals(3, $action->left);
        $this->assertEquals(5, $action->right);
        $this->assertEquals(8, $action->run());
    }

    /** @test */
    public function it_uses_custom_constructor_attributes_when_calling_the_run_method_statically()
    {
        $actionClass = new class() extends SimpleCalculator {
            protected $getAttributesFromConstructor = ['operation', 'left', 'right'];
        };

        $result = $actionClass::run('substraction', 3, 5);

        $this->assertEquals(-2, $result);
    }

    /** @test */
    public function it_throws_an_exception_when_calling_missing_methods()
    {
        try {
            (new SimpleCalculator)->missingMethod();
            $this->fail('Expected a BadMethodCallException.');
        } catch (BadMethodCallException $e) {
            $this->assertEquals('Method Lorisleiva\Actions\Tests\Actions\SimpleCalculator::missingMethod does not exist.', $e->getMessage());
        }

        try {
            SimpleCalculator::missingStaticMethod();
            $this->fail('Expected a BadMethodCallException.');
        } catch (BadMethodCallException $e) {
            $this->assertEquals('Method Lorisleiva\Actions\Tests\Actions\SimpleCalculator::missingStaticMethod does not exist.', $e->getMessage());
        }
    }
}
