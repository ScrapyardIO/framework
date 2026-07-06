<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI;

use GPIO\SPI\SPIConnectionHandle;

/**
 * GPIO\SPI\SPIConnectionHandle is abstract (and, unlike I2CConnectionHandle,
 * carries no behavior of its own to override - it's a pure marker), so
 * tests need a concrete instance to construct SPI transports with.
 */
class FakeSPIConnectionHandle extends SPIConnectionHandle {}
