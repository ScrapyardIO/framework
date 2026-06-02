<?php

namespace Waveforms\Carriers\UART\API\USB\Factory;

use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Waveforms\Carriers\UART\API\USB\Exceptions\UsbUARTException;
use Waveforms\Carriers\UART\API\USB\UsbUARTDevice;
use Waveforms\Carriers\UART\Enums\FlowControl;
use Waveforms\Carriers\UART\Enums\StopBits;
use Waveforms\Carriers\UART\Factory\UARTConnectionBuilder;

class UsbUARTConnectionBuilder extends UARTConnectionBuilder
{
    private ?FtdiProductId $device = null;

    public function device(string $device): static
    {
        $device = strtoupper($device);

        if (count(array_filter(FtdiProductId::cases(), fn (FtdiProductId $case) => $case->name === $device)) > 0) {
            $this->device = FtdiProductId::{$device};

            return $this;
        }

        throw UsbUARTException::unsupportedDevice($device);
    }

    public function boot(): UsbUARTDevice
    {
        if (is_null($this->device)) {
            throw UsbUARTException::missingDevice();
        }

        $context = ftdi_new();

        if ($context->handle < 0) {
            throw UsbUARTException::couldNotCreateContext();
        }

        ftdi_init($context);

        if (ftdi_usb_open($context, FtdiVendorId::FTDI->value, $this->device->value) !== 0) {
            $error = ftdi_get_error_string($context);
            ftdi_free($context);

            throw UsbUARTException::couldNotOpenDevice($this->device->name, $error);
        }

        ftdi_set_baudrate($context, $this->baud_rate);
        ftdi_set_line_property($context, $this->data_bits->value, $this->ftdiStopBits(), $this->parity->value);
        ftdi_setflowctrl($context, $this->ftdiFlowControl());

        return new UsbUARTDevice($context);
    }

    /**
     * Translate the carrier stop-bit setting to libftdi's ftdi_stopbits_type
     * (STOP_BIT_1 = 0, STOP_BIT_2 = 2).
     */
    private function ftdiStopBits(): int
    {
        return match ($this->stop_bits) {
            StopBits::ONE => 0,
            StopBits::TWO => 2,
        };
    }

    /**
     * Translate the carrier flow-control setting to libftdi's flow control
     * constants (SIO_DISABLE_FLOW_CTRL = 0, SIO_RTS_CTS_HS = 256, SIO_XON_XOFF_HS = 1024).
     */
    private function ftdiFlowControl(): int
    {
        return match ($this->flow_control) {
            FlowControl::NONE => 0,
            FlowControl::HARDWARE => 256,
            FlowControl::SOFTWARE => 1024,
        };
    }
}
