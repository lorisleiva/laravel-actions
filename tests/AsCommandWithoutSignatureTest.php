<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Exceptions\MissingCommandSignatureException;

class AsCommandWithoutSignatureTest
{
    use AsCommand;

    public function handle()
    {
        //
    }
}

it('throws an exception when running an action as a command without signature', function () {
    // When we register the action as a command without a signature.
    registerCommands([AsCommandWithoutSignatureTest::class]);
})->throws(
    // Then we expect a MissingCommandSignatureException.
    MissingCommandSignatureException::class,
    'The command signature is missing from your [' . AsCommandWithoutSignatureTest::class . '] action. Use `public string $commandSignature` to set it up.'
);
