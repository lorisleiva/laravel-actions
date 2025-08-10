<?php

use Lorisleiva\Actions\ActionRequest;

beforeEach(function () {
    $this->request = new class extends ActionRequest {
        public function authorize(): bool
        {
            return true;
        }
        public function rules(): array
        {
            return [];
        }
        public function handle()
        { /* no-op */
        }
    };

    $method = new \ReflectionMethod($this->request, 'hasMethod');
    $method->setAccessible(true);
    $this->hasMethod = $method;
});

it('returns false and does not throw when action is uninitialized', function () {
    expect($this->hasMethod->invoke($this->request, 'anything'))
        ->toBeFalse();
});

it('returns true for an existing method after setAction()', function () {
    // A dummy action object declaring customFoo()
    $dummyAction = new class {
        public function customFoo(): string
        {
            return 'bar';
        }
    };

    // Initialize the request’s $action
    $this->request->setAction($dummyAction);

    expect($this->hasMethod->invoke($this->request, 'customFoo'))
        ->toBeTrue();
});
