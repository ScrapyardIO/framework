<?php

namespace Waveforms\Carriers\I2C;

use Exception;
use Waveforms\Carriers\I2C\Factory\I2CConnectionBuilder;
use Waveforms\WaveformEntry;

class I2C extends WaveformEntry
{
    /**
     * @throws Exception
     */
    public static function connection(string $driver): I2CConnectionBuilder
    {
        /** @var I2CConnectionBuilder $factory */
        $factory = I2CService::getInstance()->get($driver);

        return $factory;
    }
}
