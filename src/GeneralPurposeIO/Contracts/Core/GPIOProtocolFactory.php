<?php

namespace GeneralPurposeIO\Contracts\Core;

interface GPIOProtocolFactory
{
    public function protocol(string $name);
}