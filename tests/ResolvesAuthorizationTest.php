<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Tests\Actions\SimpleCalculator;

class ResolvesAuthorizationTest extends TestCase
{
    /** @test */
    public function it_defines_authorization_logic_in_a_dedicated_method()
    {
        $attributes = [
            'operation' => 'addition',
            'left' => 1,
            'right' => 2,
        ];

        $action = new class($attributes) extends SimpleCalculator {
            public function authorize() {
                return $this->operation === 'addition';
            }
        };

        $this->assertTrue($action->passesAuthorization());
        $this->assertEquals(3, $action->run());
    }

    /** @test */
    public function it_throws_an_exception_when_user_is_not_authorized()
    {
        $this->expectException(AuthorizationException::class);

        $action = new class extends SimpleCalculator {
            public function authorize() {
                return false;
            }
        };

        $this->assertFalse($action->passesAuthorization());
        $action->run();
    }
}