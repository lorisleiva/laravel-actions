<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\WithAttributes;

class AsObjectWithAuthorizeAndRulesTest
{
    use AsObject;
    use WithAttributes;

    public function authorize(): bool
    {
        return $this->operation !== 'unauthorized';
    }

    public function rules(): array
    {
        return [
            'operation' => ['in:addition,substraction'],
            'left' => ['required', 'integer'],
            'right' => ['required', 'integer'],
        ];
    }

    public function handle()
    {
        return $this->operation === 'addition'
            ? $this->left + $this->right
            : $this->left - $this->right;
    }
}

it('lab', function () {
    $object = AsObjectWithAuthorizeAndRulesTest::run();

    dd($object);
});
