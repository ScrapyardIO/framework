<?php

namespace GeneralPurposeIO\UART\Factory;

use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\Drivers\FtdiUARTDriver;
use GeneralPurposeIO\UART\UART;
use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;

class FtdiUARTDriverFactory extends UARTDriverFactory
{
    /**
     * @throws UARTException
     */
    public function create(): UART
    {
        $this->assertReady();

        $context = ftdi_new();
        $device = $this->resolveProductId($this->device);

        if ($context->handle < 0) {
            throw UARTException::couldNotOpenUARTPort($this->device);
        }

        ftdi_init($context);

        if (ftdi_usb_open($context, FtdiVendorId::FTDI->value, $device->value) !== 0) {
            $error = ftdi_get_error_string($context);
            ftdi_free($context);

            throw UARTException::couldNotOpenDevice($device->name, $error);
        }

        // Return the chip to plain async-serial mode. Multi-protocol parts like
        // the FT232H retain a previously selected MPSSE/bitbang mode across
        // opens, which would leave D0/D1 unusable as a UART.
        ftdi_set_bitmode($context, 0x00, 0x00);

        ftdi_set_baudrate($context, $this->baud_rate);
        ftdi_set_line_property(
            $context,
            $this->data_bits->value,
            FtdiUARTDriver::ftdiStopBits($this->stop_bits),
            $this->parity->value,
        );
        ftdi_setflowctrl($context, FtdiUARTDriver::ftdiFlowControl($this->flow_control));

        return new UART(new FtdiUARTDriver($context));
    }

    /**
     * @throws UARTException
     */
    protected function resolveProductId(int|string $device): FtdiProductId
    {
        if (is_int($device) || ctype_digit((string) $device)) {
            return FtdiProductId::from((int) $device);
        }

        return match (strtolower((string) $device)) {
            'ft232h' => FtdiProductId::FT232H,
            'ft2232hl', 'ft2232h' => FtdiProductId::FT2232HL,
            'rs232l', 'ft232rl', 'ft232' => FtdiProductId::RS232L,
            default => throw UARTException::unsupportedDevice((string) $device),
        };
    }
}
