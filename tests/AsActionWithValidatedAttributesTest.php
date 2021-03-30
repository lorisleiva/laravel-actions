<?php

namespace Lorisleiva\Actions\Tests;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\WithAttributes;
use Lorisleiva\Actions\Tests\Stubs\OperationRequestedEvent;

class AsActionWithValidatedAttributesTest
{
    use AsAction;
    use WithAttributes;

    public string $commandSignature = 'my:command {operation} {left} {right}';

    public static ?int $latestResult;

    public function authorize(): bool
    {
        return $this->operation !== 'unauthorized';
    }

    public function rules(): array
    {
        return [
            'operation' => ['in:addition,substraction,multiplication'],
            'left' => ['required', 'integer'],
            'right' => ['required', 'integer'],
        ];
    }

    public function handle(array $attributes = []): int
    {
        $this->fill($attributes)->validateAttributes();

        return static::$latestResult = $this->applyStrategyFromOperation();
    }

    protected function applyStrategyFromOperation(): int
    {
        switch ($this->operation) {
            case 'substraction':
                return $this->left - $this->right;
            case 'multiplication':
                return $this->left * $this->right;
            case 'addition':
            default:
                return $this->left + $this->right;
        }
    }

    public function asController(ActionRequest $request): array
    {
        $this->fill($request->all());
        $this->set('operation', $request->route('operation'));

        return ['result' => $this->handle()];
    }

    public function asJob(int $left, string $operation, int $right): int
    {
        return $this->handle(compact('operation', 'left', 'right'));
    }

    public function asListener(OperationRequestedEvent $event): int
    {
        return $this->handle([
            'operation' => $event->operation,
            'left' => $event->left,
            'right' => $event->right,
        ]);
    }

    public function asCommand(Command $command): void
    {
        $result = $this->handle([
            'operation' => $command->argument('operation'),
            'left' => $command->argument('left'),
            'right' => $command->argument('right'),
        ]);

        $command->line('Result: ' . $result);
    }
}

beforeEach(function () {
    // Given we reset the latest result between tests.
    AsActionWithValidatedAttributesTest::$latestResult = null;
});

it('runs as an object', function () {
    // When we run the action as a plain object.
    $result = AsActionWithValidatedAttributesTest::run([
        'operation' => 'addition',
        'left' => 40,
        'right' => 2,
    ]);

    // Then we get the expected result.
    expect($result)->toBe(42);
    expect(AsActionWithValidatedAttributesTest::$latestResult)->toBe(42);
});

it('fails authorization as an object', function () {
    // Given we pass an unauthorized operation.
    AsActionWithValidatedAttributesTest::run([
        'operation' => 'unauthorized',
    ]);

    // Then we expect an authorization exception.
})->expectException(AuthorizationException::class);

it('fails validation as an object', function () {
    // Given we pass invalid data.
    AsActionWithValidatedAttributesTest::run([
        'operation' => 'invalid_operation',
        'left' => 'one',
        'right' => 'two',
    ]);

    // Then we expect a validation exception.
})->expectException(ValidationException::class);

it('runs as a controller', function () {
    // Given we have a route registered for that action.
    Route::post('compute/{operation}', AsActionWithValidatedAttributesTest::class);

    // When we run the action as an endpoint.
    $response = $this->postJson('compute/substraction', [
        'left' => 100,
        'right' => 58,
    ]);

    // Then we get the expected result.
    $response->assertOk();
    $response->assertExactJson(['result' => 42]);
    expect(AsActionWithValidatedAttributesTest::$latestResult)->toBe(42);
});

it('fails authorization as a controller', function () {
    // Given we have a route registered for that action.
    Route::post('compute/{operation}', AsActionWithValidatedAttributesTest::class);

    // When we run that endpoint with an unauthorized operation.
    $response = $this->postJson('compute/unauthorized');

    // Then we expect a forbidden response.
    $response->assertForbidden();
    $response->assertExactJson([
        'message' => 'This action is unauthorized.',
    ]);
});

it('fails validation as a controller', function () {
    // Given we have a route registered for that action.
    Route::post('compute/{operation}', AsActionWithValidatedAttributesTest::class);

    // When we run that endpoint with invalid data.
    $response = $this->postJson('compute/invalid_operation', [
        'left' => 'one',
    ]);

    // Then we expect a forbidden response.
    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'operation' => 'The selected operation is invalid.',
        'left' => 'The left must be an integer.',
        'right' => 'The right field is required.',
    ]);
});

it('runs as a job', function () {
    // When we dispatch the action as a job.
    AsActionWithValidatedAttributesTest::dispatch(6, 'multiplication', 7);

    // Then we get the expected result.
    expect(AsActionWithValidatedAttributesTest::$latestResult)->toBe(42);
});

it('fails authorization as a job', function () {
    // When we dispatch the action as a job with an unauthorized operation.
    AsActionWithValidatedAttributesTest::dispatch(6, 'unauthorized', 7);

    // Then we expect an authorization exception.
})->expectException(AuthorizationException::class);

it('fails validation as a job', function () {
    // When we dispatch the action as a job with invalid data.
    AsActionWithValidatedAttributesTest::dispatch(1, 'invalid_operation', 2);

    // Then we expect a validation exception.
})->expectException(ValidationException::class);

it('runs as a listener', function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsActionWithValidatedAttributesTest::class);

    // When we dispatch the OperationRequestedEvent.
    $results = Event::dispatch(new OperationRequestedEvent('addition', 21, 21));

    // Then we get the expected result.
    expect($results[0])->toBe(42);
    expect(AsActionWithValidatedAttributesTest::$latestResult)->toBe(42);
});

it('fails authorization as a listener', function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsActionWithValidatedAttributesTest::class);

    // When we dispatch the OperationRequestedEvent with an unauthorized operation.
    Event::dispatch(new OperationRequestedEvent('unauthorized', 21, 21));

    // Then we expect an authorization exception.
})->expectException(AuthorizationException::class);

it('fails validation as a listener', function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen(OperationRequestedEvent::class, AsActionWithValidatedAttributesTest::class);

    // When we dispatch the OperationRequestedEvent with invalid data.
    Event::dispatch(new OperationRequestedEvent('invalid_operation', 21, 21));

    // Then we expect a validation exception.
})->expectException(ValidationException::class);

it('runs as a command', function () {
    // Given we registered the action as a command.
    registerCommands([AsActionWithValidatedAttributesTest::class]);

    // When we run the action as a command.
    $command = $this->artisan('my:command multiplication 21 2');

    // Then we get the expected output.
    $command->expectsOutput('Result: 42');

    // And the expected result.
    $command->run();
    expect(AsActionWithValidatedAttributesTest::$latestResult)->toBe(42);
});

it('fails authorization as a command', function () {
    // Given we registered the action as a command.
    registerCommands([AsActionWithValidatedAttributesTest::class]);

    // When we run the action as a command with an unauthorized operation.
    $this->artisan('my:command unauthorized 21 2');

    // Then we expect an authorization exception.
})->expectException(AuthorizationException::class);

it('fails validation as a command', function () {
    // Given we registered the action as a command.
    registerCommands([AsActionWithValidatedAttributesTest::class]);

    // When we run the action as a command with an unauthorized operation.
    $this->artisan('my:command invalid_operation one two');

    // Then we expect a validation exception.
})->expectException(ValidationException::class);

it('runs as a mock', function () {
    // Given the following attributes.
    $attributes = [
        'operation' => 'substraction',
        'left' => 1,
        'right' => 2,
    ];

    // And given we mock the action with some expectations.
    AsActionWithValidatedAttributesTest::shouldRun()
        ->with($attributes)
        ->andReturn(42);

    // When we run the action with the expected arguments.
    $result = AsActionWithValidatedAttributesTest::run($attributes);

    // Then we get the expected result.
    expect($result)->toBe(42);
});

it('doesnt fail authorization as a mock', function () {
    // Given the following unauthorized attributes.
    $attributes = ['operation' => 'unauthorized'];

    // And given we mock the action with some expectations.
    AsActionWithValidatedAttributesTest::shouldRun()
        ->with($attributes)
        ->andReturn(42);

    // When we run the action with the expected unauthorized arguments.
    $result = AsActionWithValidatedAttributesTest::run($attributes);

    // Then we still get a successful result.
    expect($result)->toBe(42);
});

it('doesnt fail validation as a mock', function () {
    // Given the following invalid attributes.
    $attributes = [
        'operation' => 'invalid_operation',
        'left' => 'one',
        'right' => 'two',
    ];

    // And given we mock the action with some expectations.
    AsActionWithValidatedAttributesTest::shouldRun()
        ->with($attributes)
        ->andReturn(42);

    // When we run the action with the expected invalid arguments.
    $result = AsActionWithValidatedAttributesTest::run($attributes);

    // Then we still get a successful result.
    expect($result)->toBe(42);
});
