<?php

namespace Waveforms\Carriers\SPI;

use Exception;
use Waveforms\Carriers\SPI\Factory\SPIConnectionBuilder;
use Waveforms\WaveformEntry;

class SPI extends WaveformEntry
{
    /**
     * @throws Exception
     */
    public static function connection(string $driver): SPIConnectionBuilder
    {
        /** @var SPIConnectionBuilder $factory */
        $factory = SPIService::getInstance()->get($driver);

        return $factory;
    }
}
