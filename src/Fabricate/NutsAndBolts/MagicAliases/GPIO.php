<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static i2c(string $adapter)
 * @method static spi(string $adapter)
 * @method static pwm(string $adapter)
 * @method static uart(string $adapter)
 * @method static digitalIn(string $adapter)
 * @method static digitalOut(string $adapter)
 * @method static protocol(string $protocol)
 */
class GPIO extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio';
    }
}
