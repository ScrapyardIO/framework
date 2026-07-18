<?php

namespace GeneralPurposeIO\UART\Drivers;

use Ftdi\FTDIContext;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\StopBits;

class FtdiUARTDriver extends UARTDriver
{
    public function __construct(
        public readonly FTDIContext $context,
    ) {}

    public function close(): void
    {
        ftdi_usb_close($this->context);
        ftdi_deinit($this->context);
        ftdi_free($this->context);
    }

    public function flush(): void
    {
        ftdi_usb_purge_buffers($this->context);
    }

    public function read(int $len): array|false
    {
        $data = ftdi_read_data($this->context, $len);

        return (! $data) ? false : bytes2array($data);
    }

    public function write(array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return ftdi_write_data($this->context, $data, strlen($data));
    }

    public static function ftdiStopBits(StopBits $stop_bits): int
    {
        return match ($stop_bits) {
            StopBits::ONE => 0,
            StopBits::TWO => 2,
        };
    }

    public static function ftdiFlowControl(FlowControl $flow_control): int
    {
        return match ($flow_control) {
            FlowControl::NONE => 0,
            FlowControl::HARDWARE => 256,
            FlowControl::SOFTWARE => 1024,
        };
    }
}
