<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Queue\SyncQueue;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Decorators\JobDecorator;

class AsEncryptedJobTest implements ShouldBeEncrypted
{
    use AsJob;

    public function handle(string $secret): void
    {
        //
    }
}

class AsUnencryptedJobTest
{
    use AsJob;

    public function handle(string $secret): void
    {
        //
    }
}

function serializedCommandFor(JobDecorator $job): string
{
    $queue = new class extends SyncQueue {
        public function payloadFor(object $job): array
        {
            return json_decode($this->createPayload($job, 'default'), true);
        }
    };

    $queue->setContainer(app());

    return $queue->payloadFor($job)['data']['command'];
}

beforeEach(function () {
    // Given the application has an encryption key.
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
});

it('encrypts the job payload when the action implements ShouldBeEncrypted', function () {
    // When we build the queue payload of an encrypted action.
    $command = serializedCommandFor(AsEncryptedJobTest::makeJob('top-secret'));

    // Then the secret is not readable on the queue.
    expect($command)->not->toContain('top-secret');

    // And it decrypts back into the job.
    expect(unserialize(decrypt($command)))
        ->toBeInstanceOf(JobDecorator::class)
        ->getParameters()->toBe(['top-secret']);
});

it('does not encrypt the job payload by default', function () {
    // When we build the queue payload of a plain action.
    $command = serializedCommandFor(AsUnencryptedJobTest::makeJob('not-secret'));

    // Then it is stored as a plain serialized job.
    expect($command)->toContain('not-secret');
});
