<?php

namespace Fabricate\Process\Exceptions;

use Fabricate\Contracts\Process\ProcessResult;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyTimeoutException;
use Symfony\Component\Process\Exception\RuntimeException;

class ProcessTimedOutException extends RuntimeException
{
    /**
     * The process result instance.
     *
     * @var ProcessResult
     */
    public $result;

    /**
     * Create a new exception instance.
     *
     * @param SymfonyTimeoutException $original
     * @param ProcessResult $result
     */
    public function __construct(SymfonyTimeoutException $original, ProcessResult $result)
    {
        $this->result = $result;

        parent::__construct($original->getMessage(), $original->getCode(), $original);
    }
}
