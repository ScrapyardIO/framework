<?php

namespace GPIO\SPI;

use GPIO\Digital\MultipleDigitalPins;
use GPIO\Contracts\SPI\SPITransport as SPITransportInterface;

class SPIConnectionBus extends MultipleDigitalPins
{
    public function __construct(
        public readonly SPITransportInterface $spi,
        array $digital_pins = []
    ) {
        parent::__construct($digital_pins);
    }
}
