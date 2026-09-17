<?php

namespace GeneralPurposeIO\SPI;

use Voyager\MagicAliases\MagicAlias;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static SPIConnectionDriver driver(?string $name = null)
 */
class SPI extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.spi';
    }
}