<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorForStringEvents;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithValidation;

class ActionsAsListenersTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->make('events')->listen('string_event', SimpleCalculatorForStringEvents::class);
        $app->make('events')->listen(get_class($this->newEvent()), SimpleCalculator::class);
        $app->make('events')->listen(get_class($this->newValidatedEvent()), SimpleCalculatorWithValidation::class);
    }

    /** @test */
    public function actions_can_be_used_as_listeners_with_public_properties_of_event_as_attributes()
    {
        $event = $this->newEvent('addition', 3, 5);

        $results = $this->app->make('events')->dispatch($event);

        $this->assertEquals('addition', $event->operation);
        $this->assertEquals(3, $event->left);
        $this->assertEquals(5, $event->right);
        $this->assertEquals(8, $results[0]);
    }

    /** @test */
    public function it_can_define_custom_logic_for_get_attributes_from_event_payload()
    {
        $payload = ['addition', 3, 5];

        $results = $this->app->make('events')->dispatch('string_event', $payload);

        $this->assertEquals(8, $results[0]);
    }

    /** @test */
    public function it_throws_an_exception_when_the_action_is_authorized()
    {
        $this->expectException(AuthorizationException::class);

        $event = $this->newValidatedEvent('unauthorized');

        $this->app->make('events')->dispatch($event);
    }

    /** @test */
    public function it_redirects_back_when_the_action_is_not_validated()
    {
        $this->expectException(ValidationException::class);

        $event = $this->newValidatedEvent('invalid');

        $this->app->make('events')->dispatch($event);
    }

    protected function newEvent($operation = 'addition', $left = 1, $right = 1)
    {
        return new class($operation, $left, $right) {
            public $operation;
            public $left;
            public $right;

            public function __construct($operation, $left, $right)
            {
                $this->operation = $operation;
                $this->left = $left;
                $this->right = $right;
            }
        };
    }

    protected function newValidatedEvent($operation = 'addition', $left = 1, $right = 1)
    {
        return new class($operation, $left, $right) {
            public $operation;
            public $left;
            public $right;

            public function __construct($operation, $left, $right)
            {
                $this->operation = $operation;
                $this->left = $left;
                $this->right = $right;
            }
        };
    }
}