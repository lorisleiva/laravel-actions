<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

class AsActionTest
{
    use AsAction;

    public string $commandSignature = 'my:command {operation} {left} {right}';

    public static ?int $latestResult;

    public function handle(string $operation, int $left, int $right): int
    {
        $strategy = $this->getStrategyFromOperation($operation);

        return static::$latestResult = $strategy($left, $right);
    }

    protected function getStrategyFromOperation(string $operation): Closure
    {
        return match ($operation) {
            'substraction' => fn (int $left, int $right) => $left - $right,
            'multiplication' => fn (int $left, int $right) => $left * $right,
            default => fn (int $left, int $right) => $left + $right,
        };
    }

    public function asController(ActionRequest $request): array
    {
        $result = $this->handle(
            $request->route('operation'),
            (int) $request->get('left'),
            (int) $request->get('right'),
        );

        return compact('result');
    }

    public function asJob(int $left, string $operation, int $right): int
    {
        return $this->handle($operation, $left, $right);
    }

    public function asListener(OperationRequestedEvent $event): int
    {
        return $this->handle(
            $event->operation,
            $event->left,
            $event->right,
        );
    }

    public function asCommand(Command $command): void
    {
        $result = $this->handle(
            $command->argument('operation'),
            $command->argument('left'),
            $command->argument('right'),
        );

        $command->line('Result: ' . $result);
    }
}

beforeEach(function () {
    // Given we reset the latest result between tests.
    AsActionTest::$latestResult = null;
});

it('runs as an object', function () {
    // When we run the action as a plain object.
    $result = AsActionTest::run('addition', 40, 2);

    // Then we get the expected result.
    expect($result)->toBe(42);
    expect(AsActionTest::$latestResult)->toBe(42);
});

it('runs as a controller', function () {
    // Given we have a route registered for that action.
    Route::post('compute/{operation}', AsActionTest::class);

    // When we run the action as an endpoint.
    $response = $this->postJson('compute/substraction', [
        'left' => 100,
        'right' => 58,
    ]);

    // Then we get the expected result.
    $response->assertOk();
    $response->assertExactJson(['result' => 42]);
    expect(AsActionTest::$latestResult)->toBe(42);
});

it('runs as a job', function () {
    // When we dispatch the action as a job.
    AsActionTest::dispatch(6, 'multiplication', 7);

    // Then we get the expected result.
    expect(AsActionTest::$latestResult)->toBe(42);
});

it('runs as a listener', function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsActionTest::class);

    // When we dispatch the OperationRequestedEvent.
    $results = Event::dispatch(new OperationRequestedEvent('addition', 21, 21));

    // Then we get the expected result.
    expect($results[0])->toBe(42);
    expect(AsActionTest::$latestResult)->toBe(42);
});

it('runs as a command', function () {
    // Given we registered the action as a command.
    registerCommands([AsActionTest::class]);

    // When we run the action as a command.
    $command = $this->artisan('my:command multiplication 21 2');

    // Then we get the expected output.
    $command->expectsOutput('Result: 42');

    // And the expected result.
    $command->run();
    expect(AsActionTest::$latestResult)->toBe(42);
});

it('runs as a mock', function () {
    // Given we mock the action with some expectations.
    AsActionTest::shouldRun()
        ->with('substraction', 1, 2)
        ->andReturn(42);

    // When we run the action with the expected arguments.
    $result = AsActionTest::run('substraction', 1, 2);

    // Then we get the expected result.
    expect($result)->toBe(42);
});
