<?php

namespace GPIO\Contracts\Common;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;

interface GPIODriverAdapter
{
    public function close(GPIOConnectionHandleInterface $handle): void;
}
