<?php

namespace GeneralPurposeIO\I2C;

use GeneralPurposeIO\Digital\MultipleDigitalPins;

class I2CConnectionBus extends MultipleDigitalPins
{
    /**
     * @param  array<string, \GeneralPurposeIO\Digital\DigitalPin>  $digital_pins
     * @param  bool  $shares_i2c_context  When true (MPSSE), digital pins reuse the I2C context — only close I2C.
     */
    public function __construct(
        public readonly I2C $i2c,
        array $digital_pins = [],
        protected readonly bool $shares_i2c_context = false,
    ) {
        parent::__construct($digital_pins);
    }

    public function close(): void
    {
        $this->i2c->close();

        if (! $this->shares_i2c_context && count($this->pins) > 0) {
            parent::close();
        }
    }
}
