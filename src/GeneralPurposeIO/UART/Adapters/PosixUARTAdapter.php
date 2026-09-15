<?php

namespace GeneralPurposeIO\UART\Adapters;

use GeneralPurposeIO\Common\ConfirmPOSIXDependencies;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\Factory\PosixUARTFactory;
use GeneralPurposeIO\UART\UARTCommunicationAdapter;

class PosixUARTAdapter extends UARTCommunicationAdapter
{
    /**
     * @throws UARTException
     */
    public function device(string $device): PosixUARTFactory
    {
        if (! file_exists($device)) {
            throw UARTException::couldNotOpenUARTPort($device);
        }

        return new PosixUARTFactory($device);
    }

    /**
     * @throws GPIOException
     */
    protected function confirmDependencies(): void
    {
        ConfirmPOSIXDependencies::run('UART');

        if (!function_exists('uart_open')) {
            throw new GPIOException('The UART POSIX adapter requires the UART package. Require it with composer require microscrap/uart');
        }
    }
}