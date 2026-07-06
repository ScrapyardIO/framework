<?php

namespace GPIO\Contracts\Common;

interface GPIOConnectionFactory
{
    public function create(): GeneralPurposeIO|GPIOConnectionBus;
}
