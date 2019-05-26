<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Stubs\User;

class UpdateProfileDetails extends Action
{
    public function authorize(User $user)
    {
        return $user->role !== 'cannot_update_name';
    }

    public function rules()
    {
        return [
            'name' => 'not_in:invalid_name',
        ];
    }

    public function handle(User $user, $name)
    {
        $user->name = $name;

        return "UpdateProfileDetails ran as {$this->runningAs}";
    }
}