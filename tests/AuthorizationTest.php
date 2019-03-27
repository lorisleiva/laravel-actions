<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class AuthorizationTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_when_user_is_not_authorized()
    {
        $this->expectException(AuthorizationException::class);

        $action = $this->authorizeWith(function () {
            return false;
        });

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
                return $this->callback->__invoke();
            }
        };
    }
}