<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\UART\DataBits;
use GPIO\Contracts\UART\FlowControl;
use GPIO\Contracts\UART\Parity;
use GPIO\Contracts\UART\StopBits;
use GPIO\Contracts\UART\UARTConnectionHandle as UARTConnectionHandleInterface;
use GPIO\Contracts\UART\UARTDriverAdapter;
use GPIO\Contracts\UART\UARTTransport;
use RuntimeException;

/**
 * A no-op UART driver adapter, so tests can exercise carrier/factory wiring
 * without any real hardware driver (FTDI, MPSSE, etc.) being installed.
 */
class FakeUARTDriverAdapter implements UARTDriverAdapter
{
    public function buildConnection(
        string|int $port_device,
        int $baud_rate = 9600,
        Parity $parity = Parity::NONE,
        StopBits $stop_bits = StopBits::ONE,
        DataBits $data_bits = DataBits::EIGHT,
        FlowControl $flow_control = FlowControl::NONE
    ): UARTTransport {
        throw new RuntimeException('FakeUARTDriverAdapter::buildConnection() is not implemented.');
    }

    public function flush(UARTConnectionHandleInterface $handle): void
    {
        //
    }

    public function read(int $len, UARTConnectionHandleInterface $handle): array|false
    {
        return false;
    }

    public function write(string|array $data, UARTConnectionHandleInterface $handle): int
    {
        return 0;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        //
    }
}
