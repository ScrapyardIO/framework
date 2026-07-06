<?php

namespace GPIO\I2C;

use GPIO\Digital\MultipleDigitalPins;
use GPIO\Contracts\I2C\I2CTransport as I2CTransportInterface;

class I2CConnectionBus extends MultipleDigitalPins
{
    public function __construct(
        public readonly I2CTransportInterface $i2c,
        array $digital_pins = []
    ) {
        parent::__construct($digital_pins);
    }
}
