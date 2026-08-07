<?php

namespace Fabricate\Http\Client\Events;

use Fabricate\Http\Client\ConnectionException;
use Fabricate\Http\Client\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var \Fabricate\Http\Client\Request
     */
    public $request;

    /**
     * The exception instance.
     *
     * @var \Fabricate\Http\Client\ConnectionException
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \Fabricate\Http\Client\Request  $request
     * @param  \Fabricate\Http\Client\ConnectionException  $exception
     */
    public function __construct(Request $request, ConnectionException $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }
}
