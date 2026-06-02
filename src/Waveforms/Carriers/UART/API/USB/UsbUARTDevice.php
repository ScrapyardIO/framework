<?php

namespace Waveforms\Carriers\UART\API\USB;

use Ftdi\FTDIContext;
use Waveforms\Carriers\UART\UARTDevice;

class UsbUARTDevice extends UARTDevice
{
    public function __construct(
        protected FTDIContext $context
    ) {}

    public function read(int $len): array|false
    {
        $data = ftdi_read_data($this->context, $len);

        if ($data === false) {
            return false;
        }

        return bytes2array($data);
    }

    public function write(string|array $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return ftdi_write_data($this->context, $data, strlen($data));
    }

    public function flush(): void
    {
        ftdi_usb_purge_buffers($this->context);
    }

    public function close(): void
    {
        ftdi_usb_close($this->context);
        ftdi_deinit($this->context);
        ftdi_free($this->context);
    }
}
