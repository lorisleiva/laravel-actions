<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Tests\Stubs\User;
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

    /** @test */
    public function it_provides_a_shortcut_for_gate_checks()
    {
        Gate::define('perform-calculation', function (?User $user, $operation) {
            return $operation === 'addition';
        });

        $action = new class(['operation' => 'addition']) extends SimpleCalculator {
            public function authorize() {
                return $this->can('perform-calculation', $this->operation);
            }
        };

        $this->assertTrue($action->passesAuthorization());
    }
}