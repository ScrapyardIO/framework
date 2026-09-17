<?php

namespace GeneralPurposeIO\PWM;

use Voyager\MagicAliases\MagicAlias;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static PWMConnectionDriver driver(?string $name = null)
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
