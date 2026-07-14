<?php

namespace BareMetal\Sensors\Acceleration\MagicAliases;

use ScrapyardIO\NutsAndBolts\MagicAliases\MagicAlias;
use BareMetal\Contracts\Sensors\Accelerometry\Accelerometer as SensorAccelerometer;

/**
 * @method static SensorAccelerometer get()
 * @method static SensorAccelerometer sensor(string $sensor = 'default')
 */
class Accelerometer extends MagicAlias
{
    /**
     * Get the registered name of the component.
     */
    protected static function getAliasAccessor(): string
    {
        return 'accelerometer';
    }
}
