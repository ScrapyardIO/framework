<?php

namespace GeneralPurposeIO\Common;

use GeneralPurposeIO\Contracts\Common\GPIOException;

class ConfirmPOSIXDependencies
{
    /**
     * @throws GPIOException
     */
    public static function run(string $protocol): void
    {
        if (!extension_loaded('posi')) {
            throw new GPIOException("The {$protocol} POSIX adapter requires the ext-posi extension. Install it with pie install php-io-extension/posi");
        }

        if (!function_exists('posix_open')) {
            throw new GPIOException("The {$protocol} POSIX adapter requires the POSIX package. Require it with composer require microscrap/posix");
        }
    }
}