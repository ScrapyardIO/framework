<?php

namespace Fabricate\Http\Client\Events;

use Fabricate\Http\Client\Request;
use Fabricate\Http\Client\Response;

class ResponseReceived
{
    /**
     * The request instance.
     *
     * @var \Fabricate\Http\Client\Request
     */
    public $request;

    /**
     * The response instance.
     *
     * @var \Fabricate\Http\Client\Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param  \Fabricate\Http\Client\Request  $request
     * @param  \Fabricate\Http\Client\Response  $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
