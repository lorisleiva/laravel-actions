<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Tests\Stubs\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Tests\Actions\UpdateProfile;

class NestedActionsTest extends TestCase
{
    /** @test */
    public function an_action_can_conditionally_delegate_to_other_actions()
    {
        $user = new User([
            'name' => 'Alice',
            'avatar' => 'alice.jpg',
        ]);

        $this->assertEquals('Alice', $user->name);
        $this->assertEquals('alice.jpg', $user->avatar);

        // Here UpdateProfile delegates to UpdateProfileDetails.
        (new UpdateProfile)->run([
            'user' => $user,
            'name' => 'Bob'
        ]);

        $this->assertEquals('Bob', $user->name);
        $this->assertEquals('alice.jpg', $user->avatar);

        // Here UpdateProfile delegates to UpdateProfilePicture.
        (new UpdateProfile)->run([
            'user' => $user,
            'avatar' => 'bob.png',
        ]);

        $this->assertEquals('Bob', $user->name);
        $this->assertEquals('bob.png', $user->avatar);
    }

    /** @test */
    public function authorization_errors_are_delegated()
    {
        $this->expectNotToPerformAssertions();

        try {
            $user = new User(['role' => 'cannot_update_name']);
            (new UpdateProfile)->run(['user' => $user, 'name' => 'some name']);
            $this->fail('Expected a AuthorizationException');
        } catch (AuthorizationException $e) {
            //
        }

        try {
            $user = new User(['role' => 'cannot_update_avatar']);
            (new UpdateProfile)->run(['user' => $user, 'avatar' => 'some avatar']);
            $this->fail('Expected a AuthorizationException');
        } catch (AuthorizationException $e) {
            //
        }
    }

    /** @test */
    public function validation_errors_are_delegated()
    {
        try {
            (new UpdateProfile)->run(['user' => new User, 'name' => 'invalid_name']);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals(['name'], array_keys($e->errors()));
        }

        try {
            (new UpdateProfile)->run(['user' => new User, 'avatar' => 'invalid_avatar']);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals(['avatar'], array_keys($e->errors()));
        }
    }
}