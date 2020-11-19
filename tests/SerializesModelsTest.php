<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Lorisleiva\Actions\Tests\Actions\UpdateProfileDetails;

class SerializesModelsTest extends TestCase
{
    use SerializesAndRestoresModelIdentifiers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
    }

    /** @test */
    public function it_serializes_models_within_action_attributes()
    {
        $user = $this->createUser([
            'name' => 'Action San',
        ]);

        $action = new UpdateProfileDetails(['user' => $user, 'name' => 'Laravel San']);

        $this->assertNotFalse(
            strpos(serialize($action), serialize($this->getSerializedPropertyValue($user)))
        );
    }

    /** @test */
    public function it_unserializes_models_within_action_attributes()
    {
        $user = $this->createUser([
            'name' => 'Action San',
        ]);

        $action = new UpdateProfileDetails(['user' => $user, 'name' => 'Laravel San']);
        $rehydratedAction = unserialize(serialize($action));

        $this->assertTrue(
            $user->is($rehydratedAction->get('user'))
        );
    }
}