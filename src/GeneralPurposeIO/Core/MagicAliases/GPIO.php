<?php

namespace GeneralPurposeIO\Core\MagicAliases;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\Common\GPIOCommunicationAdapterManager as AdapterManager;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static AdapterManager protocol(string $name)
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