<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Stubs\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolvesMethodDependenciesTest extends TestCase
{
    /** @test */
    public function it_populates_arguments_from_attributes()
    {
        $attributes = [
            'operation' => 'addition',
            'left' => 3,
            'right' => 5,
        ];

        $action = new class($attributes) extends Action {
            public function handle($operation, $left, $right) {
                return compact('operation', 'left', 'right');
            }
        };

        $result = $action->run();
        $this->assertEquals('addition', $result['operation']);
        $this->assertEquals(3, $result['left']);
        $this->assertEquals(5, $result['right']);
    }

    /** @test */
    public function it_recognizes_snake_cased_attributes()
    {
        $attributes = [
            'dummy_snaked_case' => 42,
            'created_at' => '2019-05-12',
        ];

        $action = new class($attributes) extends Action {
            public function handle($dummySnakedCase, $createdAt) {
                return compact('dummySnakedCase', 'createdAt');
            }
        };

        $result = $action->run();
        $this->assertEquals(42, $result['dummySnakedCase']);
        $this->assertEquals('2019-05-12', $result['createdAt']);
    }

    /** @test */
    public function it_returns_null_or_default_when_attribute_is_missing()
    {
        $action = new class() extends Action {
            public function handle($operation, $left = 3) {
                return compact('operation', 'left');
            }
        };

        $result = $action->run();
        $this->assertNull($result['operation']);
        $this->assertEquals(3, $result['left']);
    }

    /** @test */
    public function it_resolves_type_hinted_attributes_from_the_container()
    {
        $action = new class() extends Action {
            public function handle(Dummy $dummy, Request $request) {
                return compact('dummy', 'request');
            }
        };

        $result = $action->run();
        $this->assertTrue($result['dummy'] instanceof Dummy);
        $this->assertTrue($result['request'] instanceof Request);
    }

    /** @test */
    public function it_resolves_type_hinted_models_using_route_model_binding()
    {
        $this->loadLaravelMigrations();
        $this->createUser([
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
        ]);

        $action = new class(['user' => 1]) extends Action {
            public function handle(User $user) {
                return $user;
            }
        };

        $user = $action->run();
        $this->assertTrue($user instanceof User);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john.doe@gmail.com', $user->email);
    }

    /** @test */
    public function it_saves_type_hinted_models_that_have_been_resolved()
    {
        $this->loadLaravelMigrations();
        $this->createUser([
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
        ]);

        $action = new class(['user' => 1]) extends Action {
            public function handle(User $user) {}
        };

        $this->assertEquals(1, $action->user);
        $this->assertFalse($action->user instanceof User);
        $action->run();
        $this->assertTrue($action->user instanceof User);
    }

    /** @test */
    public function it_throws_an_exception_when_route_model_binding_fails()
    {
        $this->loadLaravelMigrations();

        $action = new class(['user' => 42]) extends Action {
            public function handle(User $user) {}
        };

        $this->expectException(ModelNotFoundException::class);
        $action->run();
    }

    /** @test */
    public function it_prioritizes_parameters_over_data_when_resolving_arguments_for_a_controller()
    {
        $this->loadLaravelMigrations();
        $user = $this->createUser();

        $action = new class extends Action {
            public function handle(User $user) {
                return [
                    'parameter' => $user,
                    'attribute' => $this->user,
                ];
            }
        };

        $request = $this->createRequest('GET', '/action/{user}', '/action/1', [
            'user' => 'User provided as request payload.',
        ]);

        $result = $action->runAsController($request);

        $this->assertEquals($user->id, $result['parameter']->id);
        $this->assertEquals('User provided as request payload.', $result['attribute']);
    }
}

class Dummy
{
    //
}
