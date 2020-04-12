<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\Action;

class UpdateProfile extends Action
{
    protected $commandSignature = 'profile:update {--avatar : Determine if we should update the avatar or the profile details}';

    public function getAttributesFromCommand(Command $command): array
    {
        return $command->option('avatar') ? ['avatar' => 'my_avatar.png'] : [];
    }

    public function handle()
    {
        if ($this->has('avatar')) {
            return $this->delegateTo(UpdateProfilePicture::class);
        }

        return $this->delegateTo(UpdateProfileDetails::class);
    }
}
