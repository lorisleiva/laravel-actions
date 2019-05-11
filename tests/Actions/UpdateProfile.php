<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Tests\Stubs\User;
use Lorisleiva\Actions\Tests\Actions\TestAction;

class UpdateProfile extends TestAction
{
    public function handle(User $user)
    {
        if ($this->has('avatar')) {
            return UpdateProfilePicture::createFrom($this)->run();
        }

        return UpdateProfileDetails::createFrom($this)->run();
    }
}