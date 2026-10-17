<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Bus;
use Lorisleiva\Actions\Concerns\AsJob;

class AsJobWithChainTest
{
    use AsJob;

    public static array $handled = [];

    public function handle(int $id): void
    {
        static::$handled[] = $id;
    }
}

beforeEach(function () {
    // Given we use a real queue connection instead of a fake one.
    config()->set('queue.default', 'sync');

    // And reset the static variables.
    AsJobWithChainTest::$handled = [];
});

it('runs the whole chain on a real queue connection', function () {
    // When we dispatch a job with a chain.
    AsJobWithChainTest::withChain([
        AsJobWithChainTest::makeJob(2),
        AsJobWithChainTest::makeJob(3),
    ])->dispatch(1);

    // Then every job of the chain was handled in order.
    expect(AsJobWithChainTest::$handled)->toBe([1, 2, 3]);
});

it('returns the same value as dispatching a chain through Laravel', function () {
    // Given the value Laravel returns when dispatching a similar chain.
    $expected = Bus::chain([
        AsJobWithChainTest::makeJob(1),
        AsJobWithChainTest::makeJob(2),
    ])->dispatch();

    // When we dispatch the chain through the action.
    $result = AsJobWithChainTest::withChain([
        AsJobWithChainTest::makeJob(2),
    ])->dispatch(1);

    // Then we get the same value back.
    expect($result)->toBe($expected);
});
