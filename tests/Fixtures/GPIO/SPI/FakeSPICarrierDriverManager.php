<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI;

use GPIO\Common\CarrierDriverManager;
use GPIO\Contracts\SPI\SPIDriverAdapter as SPIDriverAdapterInterface;

/**
 * A fake carrier whose spi driver records exactly what it was asked to
 * build, so SPIConnectionFactory can be unit-tested without any real
 * hardware carrier.
 */
class FakeSPICarrierDriverManager extends CarrierDriverManager
{
    public function __construct()
    {
        parent::__construct('fake-spi');
    }

    protected function createSPIDriver(): SPIDriverAdapterInterface
    {
        return new FakeSPIDriverAdapter;
    }
}
