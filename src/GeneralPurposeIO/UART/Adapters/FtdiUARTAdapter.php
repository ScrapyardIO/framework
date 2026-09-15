<?php

namespace GeneralPurposeIO\UART\Adapters;

use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\UART\UARTCommunicationAdapter;
use GeneralPurposeIO\UART\Factory\FtdiUARTFactory;
use Microscrap\Bindings\FTDI\Enums\FtdiProductId;

class FtdiUARTAdapter extends UARTCommunicationAdapter
{
    public function device(FtdiProductId|int $device): FtdiUARTFactory
    {
        if (is_int($device)) {
            $device = FtdiProductId::from($device);
        }

        return new FtdiUARTFactory($device);
    }

    /**
     * @throws GPIOException
     */
    protected function confirmDependencies(): void
    {
        if (!extension_loaded('ftdi')) {
            throw new GPIOException('The UART USB adapter requires the ext-ftdi extension. Install it with pie install php-io-extension/ftdi');
        }

        if (!function_exists('ftdi_new')) {
            throw new GPIOException('The UART USB adapter requires the FTDI package. Require it with composer require microscrap/ftdi');
        }
    }
}