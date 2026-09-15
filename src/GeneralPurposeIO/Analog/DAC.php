<?php

namespace GeneralPurposeIO\Analog;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\Analog\AnalogOutputCommunicationAdapter as CommunicationAdapter;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static CommunicationAdapter adapter(string $name)
 */
class DAC extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.analog-out';
    }
}