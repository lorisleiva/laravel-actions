<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Mockery\MockInterface;

class AsFakeTest
{
    use AsObject;
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
                return fn (int $left, int $right) => $left - $right;
            case 'multiplication':
                return fn (int $left, int $right) => $left * $right;
            case 'modulo':
                return fn (int $left, int $right) => $left % $right;
            case 'addition':
            default:
                return fn (int $left, int $right) => $left + $right;
        }
    }
}

beforeEach(function () {
    // Given we clear the fake resolved instance between each test.
    AsFakeTest::clearFake();
});

it('can mock an action', function () {
    // Given we create a mock from the action.
    $mock = AsFakeTest::mock();

    // When we resolve this action from the container.
    $resolved = app(AsFakeTest::class);

    // Then the resolved instance is a mock.
    expect($resolved)->toBe($mock);
    expect($resolved)->toBeInstanceOf(MockInterface::class);
});

it('can mock an action expecting for it to run', function () {
    // Given we mock the action with some expectations.
    AsFakeTest::shouldRun()
        ->with('addition', 1, 2)
        ->andReturn(3);

    // When we run the action with the expected arguments
    $result = AsFakeTest::run('addition', 1, 2);

    // Then we receive the expected return value.
    expect($result)->toBe(3);
});

it('can mock an action expecting for it not to run', function () {
    //
});

it('can partionally mock an action', function () {
    //
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

it('can spy an action allowing it to run the handle method', function () {
    //
});
