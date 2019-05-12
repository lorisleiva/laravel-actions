<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Stubs\User;

class UpdateProfile extends Action
{
    public function handle(User $user)
    {
        if ($this->has('avatar')) {
            return UpdateProfilePicture::createFrom($this)->run();
        }

        return UpdateProfileDetails::createFrom($this)->run();
    }
}