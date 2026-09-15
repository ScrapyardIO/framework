<?php

namespace GeneralPurposeIO\Analog;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\Analog\AnalogInputCommunicationAdapter as CommunicationAdapter;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static CommunicationAdapter adapter(string $name)
 */
class ADC extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.analog-in';
    }
}