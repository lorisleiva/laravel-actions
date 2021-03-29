<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
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

    public function handle(array $attributes)
    {
        $this->fill($attributes)->validateAttributes();

        return $this->operation === 'addition'
            ? $this->left + $this->right
            : $this->left - $this->right;
    }
}

it('passes authorization and validation', function () {
    // When we pass the right arguments.
    $result = AsObjectWithAuthorizeAndRulesTest::run([
        'operation' => 'substraction',
        'left' => 8,
        'right' => 3,
    ]);

    // Then we receive the expected result.
    expect($result)->toBe(5);
});

it('fails authorization', function () {
    // When we pass an unauthorized operation.
    AsObjectWithAuthorizeAndRulesTest::run([
        'operation' => 'unauthorized',
    ]);

    // Then we expect a authorization exception.
})->expectException(AuthorizationException::class);

it('fails validation', function () {
    // When we pass a invalid data.
    AsObjectWithAuthorizeAndRulesTest::run([
        'operation' => 'invalid_operation',
        'left' => 'one',
        'right' => 'two',
    ]);

    // Then we expect a validation exception.
})->expectException(ValidationException::class);
