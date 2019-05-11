<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Tests\Stubs\User;
use Lorisleiva\Actions\Tests\Actions\TestAction;

class UpdateProfileDetails extends TestAction
{
    public function authorize()
    {
        return $this->user->role !== 'cannot_update_name';
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
    }
}