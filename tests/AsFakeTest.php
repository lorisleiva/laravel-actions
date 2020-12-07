<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Lorisleiva\Actions\Concerns\AsFake;
use Mockery\MockInterface;

class AsFakeTest
{
    use AsFake;

    public function handle(string $operation, int $left, int $right): int
    {
        $strategy = $this->getStrategyFromOperation($operation);

        return $strategy($left, $right);
    }

    protected function getStrategyFromOperation(string $operation): Closure
    {
        switch ($operation) {
            case 'substraction':
                return fn(int $left, int $right) => $left - $right;
            case 'multiplication':
                return fn(int $left, int $right) => $left * $right;
            case 'modulo':
                return fn(int $left, int $right) => $left % $right;
            case 'addition':
            default:
                return fn(int $left, int $right) => $left + $right;
        }
    }
}

it('can mock an action', function () {
    // Given we create a mock from the action.
    $mock = AsFakeTest::mock();

    // When we resolve this action from the container.
    $resolved = app(AsFakeTest::class);

    // Then the resolved instance is a mock.
    expect($resolved)->toBe($mock);
    expect($resolved)->toBeInstanceOf(MockInterface::class);
});

it('can spy an action', function () {
    // Given we create a spy from the action.
    $spy = AsFakeTest::spy();

    // When we resolve this action from the container.
    $resolved = app(AsFakeTest::class);

    // Then the resolved instance is a spy.
    expect($resolved)->toBe($spy);
    expect($resolved)->toBeInstanceOf(MockInterface::class);
});
