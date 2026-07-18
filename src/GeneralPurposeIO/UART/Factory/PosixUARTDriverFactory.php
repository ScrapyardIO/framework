<?php

namespace GeneralPurposeIO\UART\Factory;

use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\Drivers\PosixUARTDriver;
use GeneralPurposeIO\UART\UART;

class PosixUARTDriverFactory extends UARTDriverFactory
{
    /**
     * @throws UARTException
     */
    public function create(): UART
    {
        $this->assertReady();

        $path = is_string($this->device) && str_starts_with($this->device, '/')
            ? $this->device
            : (string) $this->device;

        $port = uart_open($path, $this->baud_rate);

        if (is_null($port)) {
            throw UARTException::couldNotOpenUARTPort($path);
        }

        PosixUARTDriver::configureLine(
            $port,
            $this->data_bits,
            $this->parity,
            $this->flow_control,
            $this->stop_bits,
        );

        return new UART(new PosixUARTDriver($port));
    }
}
