<?php

namespace GeneralPurposeIO\I2C;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\I2C\I2CCommunicationAdapter as CommunicationAdapter;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static CommunicationAdapter adapter(string $name)
 */
class I2C extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.i2c';
    }
}