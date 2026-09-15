<?php

namespace GeneralPurposeIO\PWM;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\PWM\PWMCommunicationAdapter as CommunicationAdapter;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static CommunicationAdapter adapter(string $name)
 */
class PWM extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.pwm';
    }
}