<?php

namespace GeneralPurposeIO\Contracts\Common;

interface CarrierDriverManager
{
    public function adapter(?string $adapter = null);
}
