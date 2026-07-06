<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C;

use GPIO\Common\CarrierDriverManager;
use GPIO\Contracts\I2C\I2CDriverAdapter as I2CDriverAdapterInterface;

/**
 * A fake carrier whose i2c driver records exactly what it was asked to
 * build, so I2CConnectionFactory can be unit-tested without any real
 * hardware carrier.
 */
class FakeI2CCarrierDriverManager extends CarrierDriverManager
{
    public function __construct()
    {
        parent::__construct('fake-i2c');
    }

    protected function createI2CDriver(): I2CDriverAdapterInterface
    {
        return new FakeI2CDriverAdapter;
    }
}
