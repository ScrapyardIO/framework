<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Digital\MultipleDigitalPins;

class SPIConnectionBus extends MultipleDigitalPins
{
    /**
     * @param  array<string, \GeneralPurposeIO\Digital\DigitalPin>  $digital_pins
     */
    public function __construct(
        public readonly SPI $spi,
        array $digital_pins = [],
        protected readonly bool $shares_spi_context = false,
    ) {
        parent::__construct($digital_pins);
    }

    public function close(): void
    {
        $this->spi->close();

        if (! $this->shares_spi_context && count($this->pins) > 0) {
            parent::close();
        }
    }
}
