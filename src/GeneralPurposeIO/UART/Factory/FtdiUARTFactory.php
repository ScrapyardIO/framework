<?php

namespace GeneralPurposeIO\UART\Factory;

use Ftdi\FTDIContext;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\StopBits;
use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\Bus\UsbUARTBus;
use GeneralPurposeIO\UART\Drivers\UsbUARTDriver;
use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;

class FtdiUARTFactory extends UARTFactory
{
    public function __construct(
        protected FtdiProductId $device,
    ) {}

    public function bus(): UsbUARTBus
    {
        return new UsbUARTBus($this->driver());
    }

    public function driver(): UsbUARTDriver
    {
        $this->assertReady();
        $context = ftdi_new();

        if ($context->handle < 0) {
            throw UARTException::couldNotOpenUARTPort($this->device->value);
        }

        if (ftdi_init($context) !== 0) {
            $error = ftdi_get_error_string($context);
            ftdi_free($context);

            throw UARTException::couldNotConfigureFtdiDevice($this->device->name, 'initialization', $error);
        }

        if (ftdi_usb_open($context, FtdiVendorId::FTDI->value, $this->device->value) !== 0) {
            $error = ftdi_get_error_string($context);
            ftdi_deinit($context);
            ftdi_free($context);

            throw UARTException::couldNotOpenFtdiDevice($this->device->name, $error);
        }

        $this->assertConfigured($context, 'async serial mode', ftdi_set_bitmode($context, 0x00, 0x00));
        $this->assertConfigured($context, 'baud rate', ftdi_set_baudrate($context, $this->baud_rate));
        $this->assertConfigured(
            $context,
            'line properties',
            ftdi_set_line_property(
                $context,
                $this->data_bits->value,
                $this->ftdiStopBits(),
                $this->parity->value,
            ),
        );
        $this->assertConfigured($context, 'flow control', ftdi_setflowctrl($context, $this->ftdiFlowControl()));

        // 1ms latency keeps short USB bulk reads responsive for sensors like LD2410C.
        if (function_exists('ftdi_set_latency_timer')) {
            $this->assertConfigured($context, 'latency timer', ftdi_set_latency_timer($context, 1));
        }

        ftdi_usb_purge_buffers($context);

        return new UsbUARTDriver($context);
    }

    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw UARTException::missingMasterDevice();
        }
    }

    private function ftdiStopBits(): int
    {
        return match ($this->stop_bits) {
            StopBits::ONE => 0,
            StopBits::TWO => 2,
        };
    }

    private function ftdiFlowControl(): int
    {
        return match ($this->flow_control) {
            FlowControl::NONE => 0,
            FlowControl::HARDWARE => 256,
            FlowControl::SOFTWARE => 1024,
        };
    }

    private function assertConfigured(FTDIContext $context, string $operation, int $result): void
    {
        if ($result === 0) {
            return;
        }

        $error = ftdi_get_error_string($context);
        ftdi_usb_close($context);
        ftdi_deinit($context);
        ftdi_free($context);

        throw UARTException::couldNotConfigureFtdiDevice($this->device->name, $operation, $error);
    }
}
