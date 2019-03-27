<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;

class TestAction extends Action
{
    protected $fake_authorize;
    protected $fake_rules;
    protected $fake_with_validator;

    public function fakeAuthorize($callback)
    {
        $this->fake_authorize = $callback;

        return $this;
    }

    public function fakeRules($rules)
    {
        $this->fake_rules = $rules;

        return $this;
    }

    public function fakeWithValidator($callback)
    {
        $this->fake_with_validator = $callback;

        return $this;
    }

    public function authorize()
    {
        if ($this->fake_authorize) {
            return $this->fake_authorize->bindTo($this)->__invoke();
        }

        return true;
    }

    public function rules()
    {
        return $this->fake_rules ?? [];
    }

    public function withValidator($validator)
    {
        if ($this->fake_with_validator) {
            $this->fake_with_validator->bindTo($this)->__invoke($validator);
        }
    }
}