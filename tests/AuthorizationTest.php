<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class AuthorizationTest extends TestCase
{
    /** @test */
    public function it_defines_authorization_logic_in_a_dedicated_method()
    {
        $action = $this->authorizeWith(function () {
            return $this->operation === 'addition';
        });

        $action->fill([
            'operation' => 'addition',
            'left' => 1,
            'right' => 2,
        ]);

        $this->assertTrue($action->passesAuthorization());
        $this->assertEquals(3, $action->run());
    }

    /** @test */
    public function it_throws_an_exception_when_user_is_not_authorized()
    {
        $this->expectException(AuthorizationException::class);

        $action = $this->authorizeWith(function () {
            return false;
        });

        $this->assertFalse($action->passesAuthorization());
        $action->run();
    }

    protected function authorizeWith($callback, ...$arguments)
    {
        return new class($callback, ...$arguments) extends SimpleCalculator {
            public function __construct($callback, ...$arguments)
            {
                parent::__construct(...$arguments);
                $this->callback = $callback;
            }

            public function authorize()
            {
                return $this->callback->bindTo($this)->__invoke();
            }
        };
    }
}