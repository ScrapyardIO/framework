<?php

namespace Waveforms\Carriers\GPIO;

use Exception;
use Waveforms\Carriers\GPIO\Factory\GPIOConnectionBuilder;
use Waveforms\WaveformEntry;

class GPIO extends WaveformEntry
{
    /**
     * @throws Exception
     */
    public static function connection(string $driver): GPIOConnectionBuilder
    {
        /** @var GPIOConnectionBuilder $factory */
        $factory = GPIOService::getInstance()->get($driver);

        return $factory;
    }
}
