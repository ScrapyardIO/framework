<?php

namespace GeneralPurposeIO\UART;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\UART\UARTCommunicationAdapter as CommunicationAdapter;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static CommunicationAdapter adapter(string $name)
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