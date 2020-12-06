<?php

namespace Lorisleiva\Actions\Exceptions;

use Exception;
use Throwable;

class MissingCommandSignatureException extends Exception
{
    public function __construct($action, $code = 0, Throwable $previous = null)
    {
        $message = sprintf(
            'The command signature is missing from your [%s] action. Use `public string $commandSignature` to set it up.',
            get_class($action)
        );

        parent::__construct($message, $code, $previous);
    }
}
