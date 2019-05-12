<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculatorWithValidation;

class RunsAsControllersTest extends TestCase
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
        $this->post('/calculator/validated/invalid')
            ->assertRedirect()
            ->assertSessionHasErrors([
                'operation', 'left', 'right'
            ]);
    }

    /** @test */
    public function it_can_be_intercepted_by_middleware()
    {
        $response = $this->post('/calculator/middleware');

        $this->assertEquals(400, $response->exception->getStatusCode());
        $this->assertEquals('Intercepted by a middleware', $response->exception->getMessage());
    }

    /** @test */
    public function it_resets_the_action_when_called_multiple_times_by_the_same_route()
    {
        // Laravel makes sure that there is only one Controller instance per route defined.
        // Therefore, when using Actions as Controller, the same Action can be used multiple
        // times when called from the same route, hence the need to reset it between calls.
        $this->post('/calculator/validated/addition', ['left' => 5])->assertSessionHasErrors('right');
        $this->post('/calculator/validated/addition', ['right' => 5])->assertSessionHasErrors('left');
        $this->post('/calculator/validated/invalid')->assertSessionHasErrors(['operation', 'right', 'left']);
    }

    /** @test */
    public function it_returns_the_result_of_the_handle_method_by_default()
    {
        $action = new class extends Action {
            public function handle() {
                return 'result from handle';
            }
        };

        $result = $action->runAsController(new Request);
        $this->assertEquals('result from handle', $result);
    }

    /** @test */
    public function it_returns_the_result_of_the_response_method_when_provided()
    {
        $action = new class extends Action {
            public function handle() {
                return 'result from handle';
            }
            public function response() {
                return 'result from response';
            }
        };

        $result = $action->runAsController(new Request);
        $this->assertEquals('result from response', $result);
    }

    /** @test */
    public function it_returns_the_result_of_the_html_response_method_when_provided_and_request_wants_html()
    {
        $action = new class extends Action {
            public function handle() {
                return 'result from handle';
            }
            public function htmlResponse() {
                return 'result from htmlResponse';
            }
        };

        $result = $action->runAsController(new Request);
        $this->assertEquals('result from htmlResponse', $result);
    }

    /** @test */
    public function it_returns_the_result_of_the_json_response_method_when_provided_and_request_wants_json()
    {
        $action = new class extends Action {
            public function handle() {
                return 'result from handle';
            }
            public function jsonResponse() {
                return 'result from jsonResponse';
            }
        };

        $request = new Request;
        $request->headers->add(['Accept' => 'application/json']);
        $result = $action->runAsController($request);
        $this->assertEquals('result from jsonResponse', $result);
    }

    /** @test */
    public function it_returns_the_result_of_the_response_method_when_everything_is_provided()
    {
        $action = new class extends Action {
            public function handle() {
                return 'result from handle';
            }
            public function response() {
                return 'result from response';
            }
            public function htmlResponse() {
                return 'result from htmlResponse';
            }
            public function jsonResponse() {
                return 'result from jsonResponse';
            }
        };

        $result = $action->runAsController(new Request);
        $this->assertEquals('result from response', $result);
    }
}