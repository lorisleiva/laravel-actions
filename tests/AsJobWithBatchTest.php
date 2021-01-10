<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsJobWithBatchTest
{
    use AsJob;

    public static int $constructed = 0;
    public static int $handled = 0;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle()
    {
        static::$handled++;
    }
}

beforeEach(function () {
    // Given we mock the queue driver.
    Queue::fake();

    // And reset the static counters.
    AsJobWithBatchTest::$constructed = 0;
    AsJobWithBatchTest::$handled = 0;

    // And we have a `job_batches` table.
    $this->artisan('migrate')->run();
    if (! Schema::hasTable('job_batches')) {
        $this->artisan('queue:batches-table')->run();
        $this->artisan('migrate')->run();
    }
});

it('can be dispatched in a batch', function () {
    // When we dispatch jobs in a batch.
    Bus::batch([
        AsJobWithBatchTest::makeJob(),
        AsJobWithBatchTest::makeJob(),
        AsJobWithBatchTest::makeJob(),
    ])->then(function () {

        // Then they all reached the handle method when the batch is completed.
        expect(AsJobWithBatchTest::$handled)->toBe(3);
    })->dispatch();

    // And all three jobs inside that batch have been dispatched.
    Queue::assertPushed(JobDecorator::class, 3);

    // And they've been constructed but not handled until the batch is completed.
    expect(AsJobWithBatchTest::$constructed)->toBe(3);
    expect(AsJobWithBatchTest::$handled)->toBe(0);
});
