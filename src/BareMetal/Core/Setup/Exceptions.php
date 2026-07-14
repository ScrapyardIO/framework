<?php

namespace BareMetal\Core\Setup;

use BareMetal\Core\Exceptions\Handler;

class Exceptions
{
    /**
     * Create a new exception handling configuration instance.
     */
    public function __construct(public Handler $handler)
    {
    }
}
