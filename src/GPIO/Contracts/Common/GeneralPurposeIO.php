<?php

namespace GPIO\Contracts\Common;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;

interface GeneralPurposeIO
{
    public function close(): void;
}
