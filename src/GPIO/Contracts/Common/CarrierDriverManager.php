<?php

namespace GPIO\Contracts\Common;

use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;

interface CarrierDriverManager
{
    public function driver(string $name): GPIODriverAdapterInterface;
}
