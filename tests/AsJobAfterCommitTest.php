<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsJob;

class AsJobAfterCommitTest implements ShouldQueueAfterCommit
{
    use AsJob;

    public static bool $handled;

    public function handle(): void
    {
        static::$handled = true;
    }
}

beforeEach(function () {
    // Given we reset the static variables.
    AsJobAfterCommitTest::$handled = false;
});

it('makes jobs that are dispatched after commit when the action implements ShouldQueueAfterCommit', function () {
    // When we make a job from the action.
    $job = AsJobAfterCommitTest::makeJob();

    // Then it is flagged to be dispatched after commit.
    expect($job->afterCommit)->toBeTrue();
});

it('waits for the surrounding transaction to commit before dispatching', function () {
    // When we dispatch the job inside a transaction.
    DB::transaction(function () {
        AsJobAfterCommitTest::dispatch();

        // Then it has not run before the transaction commits.
        expect(AsJobAfterCommitTest::$handled)->toBeFalse();
    });

    // And it has run once the transaction committed.
    expect(AsJobAfterCommitTest::$handled)->toBeTrue();
});

it('is not dispatched when the surrounding transaction rolls back', function () {
    // When we dispatch the job inside a transaction that rolls back.
    DB::beginTransaction();
    AsJobAfterCommitTest::dispatch();
    DB::rollBack();

    // Then it never ran.
    expect(AsJobAfterCommitTest::$handled)->toBeFalse();
});

it('can still opt out with beforeCommit', function () {
    // When we dispatch the job inside a transaction, opting out of after commit.
    DB::transaction(function () {
        AsJobAfterCommitTest::dispatch()->beforeCommit();

        // Then it ran straight away.
        expect(AsJobAfterCommitTest::$handled)->toBeTrue();
    });
});
