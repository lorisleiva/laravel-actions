# Job Entrypoint (`dispatch`, `asJob`)

## Scope
Use this reference when running an action through queues.

## Recap
- Lists async/sync dispatch helpers and conditional dispatch variants.
- Covers job wrapping/chaining with `makeJob`, `makeUniqueJob`, `makeUniqueUntilProcessingJob`, `makeDebounceJob`, and `withChain`.
- Lists the Laravel 13 queue attributes an action can declare (`#[Tries]`, `#[Queue]`, `#[UniqueFor]`, ...).
- Lists the queue interfaces an action can implement (`ShouldBeUnique`, `ShouldBeUniqueUntilProcessing`, `ShouldBeEncrypted`, `ShouldQueueAfterCommit`, `Interruptible`, `PreparesForDispatch`).
- Documents queue assertion helpers for tests (`assertPushed*`).
- Summarizes `JobDecorator` hooks/properties for retries, uniqueness, debouncing, timeout, and failure handling.

## Recommended pattern
- Dispatch with `Action::dispatch(...)` for async execution.
- Keep queue-specific orchestration in `asJob(...)`.
- Keep reusable business logic in `handle(...)`.

## Methods provided (`AsJob` trait)

### `dispatch`
Dispatches the action asynchronously.

```php
SendTeamReportEmail::dispatch($team);
```

### `dispatchIf`
Dispatches asynchronously only if condition is met.

```php
SendTeamReportEmail::dispatchIf($team->plan === 'premium', $team);
```

### `dispatchUnless`
Dispatches asynchronously unless condition is met.

```php
SendTeamReportEmail::dispatchUnless($team->plan === 'free', $team);
```

### `dispatchSync`
Dispatches synchronously.

```php
SendTeamReportEmail::dispatchSync($team);
```

### `dispatchNow`
Alias of `dispatchSync`.

```php
SendTeamReportEmail::dispatchNow($team);
```

### `dispatchAfterResponse`
Dispatches synchronously after the HTTP response is sent.

```php
SendTeamReportEmail::dispatchAfterResponse($team);
```

### `makeJob`
Creates a `JobDecorator` wrapper. Useful with `dispatch(...)` helper or chains.

```php
dispatch(SendTeamReportEmail::makeJob($team));
```

### `makeUniqueJob`
Creates a `UniqueJobDecorator` wrapper. Usually automatic with `ShouldBeUnique`, but can be forced. Returns a `UniqueUntilProcessingJobDecorator` when the action implements `ShouldBeUniqueUntilProcessing`.

```php
dispatch(SendTeamReportEmail::makeUniqueJob($team));
```

### `makeUniqueUntilProcessingJob`
Creates a `UniqueUntilProcessingJobDecorator` wrapper, which releases the unique lock when the job starts rather than when it finishes. Usually automatic with `ShouldBeUniqueUntilProcessing`, but can be forced.

```php
dispatch(SendTeamReportEmail::makeUniqueUntilProcessingJob($team));
```

### `makeDebounceJob`
Creates a `DebounceJobDecorator` wrapper. Used automatically when the action is debounced (`#[DebounceFor]`, `$jobDebounceFor` or `getJobDebounceFor`).

```php
$job = SendTeamReportEmail::makeDebounceJob($team);
```

### `withChain`
Attaches jobs to run after successful processing.

```php
$chain = [
    OptimizeTeamReport::makeJob($team),
    SendTeamReportEmail::makeJob($team),
];

CreateNewTeamReport::withChain($chain)->dispatch($team);
```

Equivalent using `Bus::chain(...)`:

```php
use Illuminate\Support\Facades\Bus;

Bus::chain([
    CreateNewTeamReport::makeJob($team),
    OptimizeTeamReport::makeJob($team),
    SendTeamReportEmail::makeJob($team),
])->dispatch();
```

Chain assertion example:

```php
use Illuminate\Support\Facades\Bus;

Bus::fake();

Bus::assertChained([
    CreateNewTeamReport::makeJob($team),
    OptimizeTeamReport::makeJob($team),
    SendTeamReportEmail::makeJob($team),
]);
```

### `assertPushed`
Asserts the action was queued.

```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

SendTeamReportEmail::assertPushed();
SendTeamReportEmail::assertPushed(3);
SendTeamReportEmail::assertPushed($callback);
SendTeamReportEmail::assertPushed(3, $callback);
```

`$callback` receives:
- Action instance.
- Dispatched arguments.
- `JobDecorator` instance.
- Queue name.

### `assertNotPushed`
Asserts the action was not queued.

```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

SendTeamReportEmail::assertNotPushed();
SendTeamReportEmail::assertNotPushed($callback);
```

### `assertPushedOn`
Asserts the action was queued on a specific queue.

```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

SendTeamReportEmail::assertPushedOn('reports');
SendTeamReportEmail::assertPushedOn('reports', 3);
SendTeamReportEmail::assertPushedOn('reports', $callback);
SendTeamReportEmail::assertPushedOn('reports', 3, $callback);
```

## Methods used (`JobDecorator`)

### `asJob`
Called when dispatched as a job. Falls back to `handle(...)` if missing.

```php
class SendTeamReportEmail
{
    use AsAction;

    public function handle(Team $team, bool $fullReport = false): void
    {
        // Prepare report and send it to all $team->users.
    }

    public function asJob(Team $team): void
    {
        $this->handle($team, true);
    }
}
```

### `getJobMiddleware`
Adds middleware to the queued action.

```php
public function getJobMiddleware(array $parameters): array
{
    return [new RateLimited('reports')];
}
```

### `configureJob`
Configures `JobDecorator` options.

```php
use Lorisleiva\Actions\Decorators\JobDecorator;

public function configureJob(JobDecorator $job): void
{
    $job->onConnection('my_connection')
        ->onQueue('my_queue')
        ->through(['my_middleware'])
        ->chain(['my_chain'])
        ->delay(60);
}
```

### `$jobConnection`
Defines queue connection.

```php
public string $jobConnection = 'my_connection';
```

### `$jobQueue`
Defines queue name.

```php
public string $jobQueue = 'my_queue';
```

### `$jobTries`
Defines max attempts.

```php
public int $jobTries = 10;
```

### `$jobMaxExceptions`
Defines max unhandled exceptions before failure.

```php
public int $jobMaxExceptions = 3;
```

### `$jobBackoff`
Defines retry delay seconds.

```php
public int $jobBackoff = 60;
```

### `getJobBackoff`
Defines retry delay (int or per-attempt array).

```php
public function getJobBackoff(): int
{
    return 60;
}

public function getJobBackoff(): array
{
    return [30, 60, 120];
}
```

### `$jobTimeout`
Defines timeout in seconds.

```php
public int $jobTimeout = 60 * 30;
```

### `$jobFailOnTimeout`
Marks the job as failed when it times out, instead of retrying it.

```php
public bool $jobFailOnTimeout = true;
```

### `$jobCountCrashesAsExceptions`
Counts worker crashes towards `$jobMaxExceptions`.

```php
public bool $jobCountCrashesAsExceptions = true;
```

### `$jobRetryUntil`
Defines timestamp retry deadline.

```php
public int $jobRetryUntil = 1610191764;
```

### `getJobRetryUntil`
Defines retry deadline as `DateTime`.

```php
public function getJobRetryUntil(): DateTime
{
    return now()->addMinutes(30);
}
```

### `getJobDisplayName`
Customizes queued job display name.

```php
public function getJobDisplayName(): string
{
    return 'Send team report email';
}
```

### `getJobTags`
Adds queue tags.

```php
public function getJobTags(Team $team): array
{
    return ['report', 'team:'.$team->id];
}
```

### `getJobUniqueId`
Defines uniqueness key when using `ShouldBeUnique`.

```php
public function getJobUniqueId(Team $team): int
{
    return $team->id;
}
```

### `$jobUniqueId`
Static uniqueness key alternative.

```php
public string $jobUniqueId = 'some_static_key';
```

### `getJobUniqueFor`
Defines uniqueness lock duration in seconds.

```php
public function getJobUniqueFor(Team $team): int
{
    return $team->role === 'premium' ? 1800 : 3600;
}
```

### `$jobUniqueFor`
Property alternative for uniqueness lock duration.

```php
public int $jobUniqueFor = 3600;
```

### `getJobUniqueVia`
Defines cache driver used for uniqueness lock.

```php
public function getJobUniqueVia()
{
    return Cache::driver('redis');
}
```

### `#[DebounceFor]`
Debounces the job (Laravel 13.6+): dispatches within the window replace each other and only the last one runs. `maxWait` caps how long a stream of dispatches can push it back. Also read from parent classes and traits.

```php
use Illuminate\Queue\Attributes\DebounceFor;

#[DebounceFor(60, maxWait: 300)]
class SendTeamReportEmail
{
    use AsAction;
}
```

### `getJobDebounceFor`
Defines the debounce delay in seconds. Debounces the action without the attribute, and takes precedence over it.

```php
public function getJobDebounceFor(Team $team): int
{
    return $team->role === 'premium' ? 10 : 60;
}
```

### `$jobDebounceFor`
Property alternative for the debounce delay. `maxWait` can only be set through the attribute.

```php
public int $jobDebounceFor = 60;
```

### `getJobDebounceId`
Defines the debounce key, so only dispatches with the same key replace each other.

```php
public function getJobDebounceId(Team $team): int
{
    return $team->id;
}
```

### `$jobDebounceId`
Static debounce key alternative.

```php
public string $jobDebounceId = 'some_static_key';
```

### `getJobDebounceVia`
Defines cache driver used for the debounce lock.

```php
public function getJobDebounceVia()
{
    return Cache::driver('redis');
}
```

### `$jobDeleteWhenMissingModels`
Property alternative for missing model handling.

```php
public bool $jobDeleteWhenMissingModels = true;
```

### `getJobDeleteWhenMissingModels`
Defines whether jobs with missing models are deleted.

```php
public function getJobDeleteWhenMissingModels(): bool
{
    return true;
}
```

### Queue attributes
On Laravel 13, an action can declare the same queue attributes as a native job. They are also read from parent classes and traits. A `getJob*` method always wins. Between a `$job*` property and an attribute, Laravel's own rules apply: the property wins when it holds a non-default value, or is declared on a subclass of the class carrying the attribute; otherwise the attribute wins.

| Attribute | Equivalent |
|---|---|
| `#[Backoff(10, 20)]` | `$jobBackoff` |
| `#[Connection('redis')]` | `$jobConnection` |
| `#[CountCrashesAsExceptions]` | `$jobCountCrashesAsExceptions` |
| `#[Delay(30)]` | `Action::dispatch(...)->delay(30)`. Ignored on debounced actions, as on native jobs. |
| `#[DeleteWhenMissingModels]` | `$jobDeleteWhenMissingModels` |
| `#[FailOnTimeout]` | `$jobFailOnTimeout` |
| `#[MaxExceptions(3)]` | `$jobMaxExceptions` |
| `#[Queue('reports')]` | `$jobQueue` |
| `#[Timeout(120)]` | `$jobTimeout` |
| `#[Tries(5)]` | `$jobTries` |
| `#[UniqueFor(3600)]` | `$jobUniqueFor` |
| `#[WithoutRelations]` | Serializes model arguments without their loaded relations. |

```php
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

#[Queue('reports')]
#[Tries(5)]
class SendTeamReportEmail
{
    use AsAction;
}
```

### Queue interfaces
Laravel checks these interfaces on the queued job, which is the decorator rather than the action. Implement them on the action and the decorator honours them.

- `ShouldBeUnique`: dispatched through a `UniqueJobDecorator`.
- `ShouldBeUniqueUntilProcessing`: dispatched through a `UniqueUntilProcessingJobDecorator`, swappable via `ActionManager::useUniqueUntilProcessingJobDecorator()`.
- `ShouldBeEncrypted`: the job payload is encrypted on the queue.
- `ShouldQueueAfterCommit`: the job waits for open database transactions to commit. `Action::dispatch(...)->beforeCommit()` still opts out.
- `Interruptible` (Laravel 13): `interrupted(int $signal)` is called when the worker times out or is told to stop while the job runs. The action gets an `Interruptible*JobDecorator`, swappable via `ActionManager::useInterruptibleJobDecorator()` and its unique, unique until processing and debounce equivalents.
- `PreparesForDispatch` (Laravel 13): `prepareForDispatch()` is called before dispatching, and returning `false` cancels the dispatch. The action is stateless, so to prepare the job (queue, delay...) ask for the `JobDecorator` as the first argument, as in `asJob()`.

Both methods also receive the dispatched parameters after Laravel's own arguments. The interface fixes their signatures, so declare those parameters as optional.

```php
class SendTeamReportEmail implements Interruptible, PreparesForDispatch
{
    use AsAction;

    public function prepareForDispatch(?JobDecorator $job = null, ?Team $team = null): bool
    {
        $job?->onQueue($team?->reportsQueue());

        return $team?->wantsReports() ?? true;
    }

    public function interrupted(int $signal, ?Team $team = null): void
    {
        // Release resources, record progress...
    }
}
```

```php
class SendTeamReportEmail implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use AsAction;
}
```

### `jobFailed`
Handles job failure. Receives exception and dispatched parameters.

```php
public function jobFailed(?Throwable $e, ...$parameters): void
{
    // Notify users, report errors, trigger compensations...
}
```

## Checklist
- Async/sync dispatch method matches use-case (`dispatch`, `dispatchSync`, `dispatchAfterResponse`).
- Queue config is explicit when needed (`$jobConnection`, `$jobQueue`, `configureJob`).
- Retry/backoff/timeout policies are intentional.
- `asJob(...)` delegates to `handle(...)` unless queue-specific branching is required.
- Queue tests use `Queue::fake()` and action assertions (`assertPushed*`).

## Common pitfalls
- Embedding domain logic only in `asJob(...)`.
- Forgetting uniqueness/timeout/retry controls on heavy jobs.
- Missing queue-specific assertions in tests.
- Asserting a `ShouldBeUniqueUntilProcessing` or `Interruptible` action with `Queue::assertPushed(UniqueJobDecorator::class)`; it is pushed as a different decorator, so use `Action::assertPushed()`.
- Registering a custom decorator through `ActionManager` without a matching variant. `ShouldBeUniqueUntilProcessing` and `Interruptible` actions are queued with a variant decorator, which must extend the registered one (e.g. `useUniqueUntilProcessingJobDecorator()` with a class extending your custom unique decorator and implementing `ShouldBeUniqueUntilProcessing`), otherwise `makeJob()` throws a `LogicException`.
- Making an action both `ShouldBeUnique` (or `ShouldBeUniqueUntilProcessing`) and debounced; `makeJob()` throws a `LogicException`.
- Dispatching a debounced action with `dispatch(Action::makeJob(...))`, which drops `maxWait`; use `Action::dispatch(...)`. The first job of a `withChain()` isn't debounced.

## References
- https://www.laravelactions.com/2.x/as-job.html
