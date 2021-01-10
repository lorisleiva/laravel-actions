<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsJob;

class AsJobWithBatchTest
{
    use AsJob;

    public static int $constructed = 0;
    public static int $handled = 0;
    public static ?int $latestResult;
    public static ?Batch $latestBatch;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle(int $left, int $right)
    {
        static::$handled++;
        static::$latestResult = $left + $right;
    }

    public function asJob(?Batch $batch, int $left, int $right)
    {
        static::$latestBatch = $batch;
        $this->handle($left, $right);
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobWithBatchTest::$constructed = 0;
    AsJobWithBatchTest::$handled = 0;
    AsJobWithBatchTest::$latestResult = null;
    AsJobWithBatchTest::$latestBatch = null;

    // And have a `job_batches` table.
    $this->artisan('migrate')->run();
    if (! Schema::hasTable('job_batches')) {
        $this->artisan('queue:batches-table')->run();
        $this->artisan('migrate')->run();
    }
});

it('can be dispatched in a batch', function () {
    // Given a set of 3 jobs.
    $jobs = [
        AsJobWithBatchTest::makeJob(1, 2),
        AsJobWithBatchTest::makeJob(3, 4),
        AsJobWithBatchTest::makeJob(5, 6),
    ];

    // When we dispatch these jobs in a batch.
    $batch = Bus::batch($jobs)->then(function (Batch $batch) {
        // Then they all reached the handle method when the batch is completed.
        expect(AsJobWithBatchTest::$handled)->toBe(3);
        expect($batch->totalJobs)->toBe(3);
        expect($batch->pendingJobs)->toBe(0);
    });

    // And they've been constructed but not handled until the batch is completed.
    expect(AsJobWithBatchTest::$constructed)->toBe(3);
    expect(AsJobWithBatchTest::$handled)->toBe(0);

    $batch->dispatch();
});

it('can access the batch instance from the asJob method', function () {
    // When we dispatch a batch.
    Bus::batch([AsJobWithBatchTest::makeJob(1, 2)])
        ->name('My batch name')
        ->then(function (Batch $batch) {
            // Then the Batch instance can be accessed in the asJob method.
            expect(AsJobWithBatchTest::$latestBatch->id)->toBe($batch->id);
            expect(AsJobWithBatchTest::$latestBatch->name)->toBe('My batch name');

            // And we get the expected result.
            expect(AsJobWithBatchTest::$latestResult)->toBe(3);
        })
        ->dispatch();
});

it('returns a null batch when not dispatched in a batch', function () {
    // When we dispatch a job not in a batch.
    AsJobWithBatchTest::dispatch(1, 2);

    // Then the Batch provided was null.
    expect(AsJobWithBatchTest::$latestBatch)->toBeNull();

    // And we get the expected result.
    expect(AsJobWithBatchTest::$latestResult)->toBe(3);
});
