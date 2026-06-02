<?php

namespace Waveforms\Carriers\UART\API\Native;

use microscrap\uart\src\DataObjects\UARTPort;
use microscrap\uart\src\Enums\TermiosQueue;
use Waveforms\Carriers\UART\UARTDevice;

class NativeUARTDevice extends UARTDevice
{
    public function __construct(
        protected UARTPort $port
    ) {}

    public function read(int $len): array|false
    {
        $data = uart_read($this->port, $len);

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

        return uart_write($this->port, $data);
    }

    public function flush(): void
    {
        uart_flush($this->port, TermiosQueue::TCIOFLUSH);
    }

    public function close(): void
    {
        uart_close($this->port);
    }
}
