<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\WithAttributes;

class AsActionWithValidatedAttributesAndPrepareForValidationTest
{
    use AsAction;
    use WithAttributes;

    public string $commandSignature = 'my:command {slug}';

    public static ?string $latestResult;

    public function prepareForValidation(): void
    {
        $this->fill(['slug' => Str::slug($this->get('slug'))]);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'alpha_dash'],
        ];
    }

    public function handle(array $attributes = []): string
    {
        $this->fill($attributes)->validateAttributes();

        return static::$latestResult = $this->get('slug');
    }

    public function asController(ActionRequest $request): array
    {
        $this->fill($request->all());

        return ['slug' => $this->handle()];
    }

    public function asJob(string $slug): string
    {
        return $this->handle(compact('slug'));
    }

    public function asListener(string $slug): string
    {
        return $this->handle(compact('slug'));
    }

    public function asCommand(Command $command): void
    {
        $result = $this->handle([
            'slug' => $command->argument('slug'),
        ]);

        $command->line('Slug: ' . $result);
    }
}

beforeEach(function () {
    // Given we reset the latest result between tests.
    AsActionWithValidatedAttributesAndPrepareForValidationTest::$latestResult = null;
});

it('runs prepareForValidation as an object', function () {
    // When we run the action as a plain object.
    $result = AsActionWithValidatedAttributesAndPrepareForValidationTest::run([
        'slug' => 'My Slug 123',
    ]);

    // Then we get the expected result.
    expect($result)->toBe('my-slug-123');
    expect(AsActionWithValidatedAttributesAndPrepareForValidationTest::$latestResult)->toBe('my-slug-123');
});

it('runs prepareForValidation as a controller', function () {
    // Given we have a route registered for that action.
    Route::post('slugs', AsActionWithValidatedAttributesAndPrepareForValidationTest::class);

    // When we run the action as an endpoint.
    $response = $this->postJson('slugs', [
        'slug' => 'My Slug 123',
    ]);

    // Then we get the expected result.
    $response->assertOk();
    $response->assertExactJson(['slug' => 'my-slug-123']);
    expect(AsActionWithValidatedAttributesAndPrepareForValidationTest::$latestResult)->toBe('my-slug-123');
});

it('runs prepareForValidation as a job', function () {
    // When we dispatch the action as a job.
    AsActionWithValidatedAttributesAndPrepareForValidationTest::dispatch('My Slug 123');

    // Then we get the expected result.
    expect(AsActionWithValidatedAttributesAndPrepareForValidationTest::$latestResult)->toBe('my-slug-123');
});

it('runs prepareForValidation as a listener', function () {
    // Given we are listening for the OperationRequestedEvent.
    Event::listen('slugs.create', AsActionWithValidatedAttributesAndPrepareForValidationTest::class);

    // When we dispatch the OperationRequestedEvent.
    $results = Event::dispatch('slugs.create', [
        'slug' => 'My Slug 123',
    ]);

    // Then we get the expected result.
    expect($results[0])->toBe('my-slug-123');
    expect(AsActionWithValidatedAttributesAndPrepareForValidationTest::$latestResult)->toBe('my-slug-123');
});

it('runs prepareForValidation as a command', function () {
    // Given we registered the action as a command.
    registerCommands([AsActionWithValidatedAttributesAndPrepareForValidationTest::class]);

    // When we run the action as a command.
    $command = $this->artisan('my:command "My Slug 123"');

    // Then we get the expected output.
    $command->expectsOutput('Slug: my-slug-123');

    // And the expected result.
    $command->run();
    expect(AsActionWithValidatedAttributesAndPrepareForValidationTest::$latestResult)->toBe('my-slug-123');
});
