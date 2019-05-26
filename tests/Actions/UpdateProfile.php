<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Stubs\User;

class UpdateProfile extends Action
{
    public function handle()
    {
        if ($this->has('avatar')) {
            return $this->delegateTo(UpdateProfilePicture::class);
        }

        return $this->delegateTo(UpdateProfileDetails::class);
    }
}