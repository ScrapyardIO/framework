<?php

namespace GeneralPurposeIO\I2C;

use Voyager\MagicAliases\MagicAlias;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static I2CConnectionDriver driver(?string $name = null)
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