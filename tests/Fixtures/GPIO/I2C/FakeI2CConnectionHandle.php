<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C;

use GPIO\I2C\I2CConnectionHandle;

/**
 * GPIO\I2C\I2CConnectionHandle is abstract, so tests need a concrete
 * instance carrying a configurable slave address to construct I2C
 * transports with.
 */
class FakeI2CConnectionHandle extends I2CConnectionHandle
{
    public function __construct(
        private readonly int $slave_address = 0x10,
    ) {}

    public function slaveAddress(): int
    {
        return $this->slave_address;
    }
}
