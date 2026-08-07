<?php

namespace Fabricate\Encryption;

use RuntimeException;

class MissingAppKeyException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'No application encryption key has been specified.')
    {
        parent::__construct($message);
    }
}
