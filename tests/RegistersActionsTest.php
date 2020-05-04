<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Str;
use Lorisleiva\Actions\ActionManager;
use Lorisleiva\Actions\Facades\Actions;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class RegistersActionsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->instance(ActionManager::class, new class() extends ActionManager {
            protected function getClassnameFromPathname(string $pathname): string
            {
                return 'Lorisleiva\\Actions\\Tests\\' . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($pathname, realpath(__DIR__).DIRECTORY_SEPARATOR)
                );
            }
        });
    }

    /** @test */
    public function it_can_register_all_actions_within_a_directory()
    {
        Actions::paths(__DIR__ . '/Actions');
        Actions::registerAllPaths();

        $this->assertCount(9, Actions::getRegisteredActions());
    }

    /** @test */
    public function it_only_register_actions_once()
    {
        Actions::paths(__DIR__ . '/Actions');
        Actions::registerAllPaths();
        Actions::registerAllPaths();

        $this->assertCount(9, Actions::getRegisteredActions());
    }

    /** @test */
    public function it_can_register_one_specific_action()
    {
        Actions::register(new SimpleCalculator);

        $registeredActions = Actions::getRegisteredActions();
        $this->assertCount(1, $registeredActions);
        $this->assertContains(SimpleCalculator::class, $registeredActions);
    }

    /** @test */
    public function it_can_register_one_specific_action_with_its_classname()
    {
        Actions::register(SimpleCalculator::class);

        $registeredActions = Actions::getRegisteredActions();
        $this->assertCount(1, $registeredActions);
        $this->assertContains(SimpleCalculator::class, $registeredActions);
    }

    /** @test */
    public function it_cannnot_register_the_same_action_twice()
    {
        Actions::register(SimpleCalculator::class);
        Actions::register(SimpleCalculator::class);

        $registeredActions = Actions::getRegisteredActions();
        $this->assertCount(1, $registeredActions);
        $this->assertContains(SimpleCalculator::class, $registeredActions);
    }

    /** @test */
    public function it_calls_the_registered_static_method_when_an_action_is_registered_at_most_once()
    {
        $action = new class extends SimpleCalculator {
            public static $registeredCounter = 0;

            public static function registered() 
            {
                static::$registeredCounter++;
            }
        };
        
        $this->assertEquals(0, $action::$registeredCounter);
        Actions::register($action);
        Actions::register($action);
        $this->assertEquals(1, $action::$registeredCounter);
    }

    /** @test */
    public function it_calls_the_initialized_method_when_an_action_is_instantiated()
    {
        $action = new class() extends SimpleCalculator {
            public static $initializedCalled = false;

            public function initialized() 
            {
                static::$initializedCalled = true;
            }
        };
        
        $this->assertTrue($action::$initializedCalled);
    }
}
