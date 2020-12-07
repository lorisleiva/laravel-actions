<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Mockery;
use Mockery\Exception\InvalidCountException;
use Mockery\MockInterface;
use stdClass;

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

it('uses the same mock instance when mocked multiple times', function () {
    // When we create a mock from the action twice.
    $mockA = AsFakeTest::mock();
    $mockB = AsFakeTest::mock();

    // Then they are the same mock instance.
    expect($mockA)->toBe($mockB);

    // And the action resolves to that same instance.
    expect(app(AsFakeTest::class))->toBe($mockA);
});

it('can mock an action expecting for it to run', function () {
    // Given we mock the action with some expectations.
    AsFakeTest::shouldRun()
        ->with('addition', 1, 2)
        ->andReturn(3);

    // When we run the action with the expected arguments.
    $result = AsFakeTest::run('addition', 1, 2);

    // Then we receive the expected return value.
    expect($result)->toBe(3);
});

it('can mock an action expecting for it not to run', function () {
    // Given we mock the action with the
    // expectation that it should not run.
    AsFakeTest::shouldNotRun();

    // When we run the action.
    AsFakeTest::run('addition', 1, 2);

    // Then we expect a mocking exception.
    Mockery::close();
})->throws(InvalidCountException::class);

it('can partionally mock an action', function () {
    // Given we partially mock one method of the action.
    AsFakeTest::partialMock()
        ->shouldReceive('getStrategyFromOperation')
        ->with('modulo')
        ->andReturn(fn (int $left, int $right) => $left % $right);

    // When we run the action with the expected arguments.
    $result = AsFakeTest::run('modulo', 15, 7);

    // Then we receive the expected return value.
    expect($result)->toBe(1);
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

it('uses the same spy instance when spied multiple times', function () {
    // When we create a spy from the action twice.
    $spyA = AsFakeTest::spy();
    $spyB = AsFakeTest::spy();

    // Then they are the same spy instance.
    expect($spyA)->toBe($spyB);

    // And the action resolves to that same instance.
    expect(app(AsFakeTest::class))->toBe($spyA);
});

it('can spy an action allowing it to run the handle method', function () {
    // Given we spy the action by providing a mocked return value.
    AsFakeTest::allowToRun()->andReturn(3);

    // When we run the action with same arguments.
    $result = AsFakeTest::run('addition', 1, 2);

    // Then we receive the mocked return value.
    expect($result)->toBe(3);

    // And we can make additional expectations afterwards.
    AsFakeTest::spy()
        ->shouldHaveReceived('handle')
        ->with('addition', 1, 2);
});

it('provides a helper method to know if the action is faked or not', function () {
    // Given an action that has not been mocked.
    expect(AsFakeTest::isFake())->toBeFalse();

    // When we mock the action.
    AsFakeTest::mock();

    // Then it is now fake.
    expect(AsFakeTest::isFake())->toBeTrue();
});

it('can clear the fake instance from an action', function () {
    // Given an action that has been mocked.
    AsFakeTest::mock();
    expect(AsFakeTest::isFake())->toBeTrue();

    // When we clear the fake instance.
    AsFakeTest::clearFake();

    // Then it is no longer faked.
    expect(AsFakeTest::isFake())->toBeFalse();

    // And resolves itself from the container.
    $resolvedA = app(AsFakeTest::class);
    expect($resolvedA)->toBeInstanceOf(AsFakeTest::class);

    // And each resolution creates its own instance.
    $resolvedB = app(AsFakeTest::class);
    expect($resolvedA)->not()->toBe($resolvedB);
});

it('keeps the original shared instance if any when clearing the fake instance', function () {
    // Given an action that is shared within the container.
    app()->instance(AsFakeTest::class, $originalInstance = new stdClass());

    // And then mocked later on.
    AsFakeTest::mock();
    expect(AsFakeTest::isFake())->toBeTrue();

    // When we clear the fake instance.
    AsFakeTest::clearFake();

    // Then it is no longer faked.
    expect(AsFakeTest::isFake())->toBeFalse();

    // And the original shared binding has been restored.
    $resolvedA = app(AsFakeTest::class);
    $resolvedB = app(AsFakeTest::class);
    expect($resolvedA)->toBe($originalInstance);
    expect($resolvedB)->toBe($originalInstance);
});
