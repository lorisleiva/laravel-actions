<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

class AsActionTest
{
    use AsAction;

    public static ?int $latestResult;

    public function handle(string $operation, int $left, int $right): int
    {
        $strategy = $this->getStrategyFromOperation($operation);

        return static::$latestResult = $strategy($left, $right);
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

    public function asController(ActionRequest $request)
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

    public function asListener(OperationRequestedEvent $event)
    {
        return $this->handle(
            $event->operation,
            $event->left,
            $event->right,
        );
    }

    public function asCommand()
    {
        //
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
