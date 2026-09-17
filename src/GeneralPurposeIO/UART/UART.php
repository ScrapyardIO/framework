<?php

namespace GeneralPurposeIO\UART;

use Voyager\MagicAliases\MagicAlias;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static UARTConnectionDriver driver(?string $name = null)
 */
class UART extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.uart';
    }
}