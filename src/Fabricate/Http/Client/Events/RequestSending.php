<?php

namespace Fabricate\Http\Client\Events;

use Fabricate\Http\Client\Request;

class RequestSending
{
    /**
     * The request instance.
     *
     * @var \Fabricate\Http\Client\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Fabricate\Http\Client\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
