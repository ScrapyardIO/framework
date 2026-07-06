<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\UART\DataBits;
use GPIO\Contracts\UART\FlowControl;
use GPIO\Contracts\UART\Parity;
use GPIO\Contracts\UART\StopBits;
use GPIO\Contracts\UART\UARTConnectionHandle as UARTConnectionHandleInterface;
use GPIO\Contracts\UART\UARTDriverAdapter;
use GPIO\Contracts\UART\UARTTransport;
use GPIO\UART\UART;

/**
 * A recording UART driver adapter: every method call is captured so tests
 * can assert exactly what the factory/transport forwarded to the driver,
 * mirroring FakeI2CDriverAdapter and FakeSPIDriverAdapter.
 *
 * This is a distinct fixture from DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\FakeUARTDriverAdapter,
 * which is a no-op used purely to wire up cross-protocol carrier fixtures.
 */
class FakeUARTDriverAdapter implements UARTDriverAdapter
{
    /** Returned by buildConnection() unless overridden per-test. */
    public ?UARTTransport $buildConnectionReturnValue = null;

    /** Every argument list passed to buildConnection(). */
    public array $buildConnectionCalls = [];

    /** Value returned by every call to read(), unless overridden per-test. */
    public array|false $readReturnValue = false;

    /** Every [$len, $handle] pair passed to read(). */
    public array $readCalls = [];

    /** Value returned by every call to write(), unless overridden per-test. */
    public int $writeReturnValue = 0;

    /** Every [$data, $handle] pair passed to write(). */
    public array $writeCalls = [];

    /** Every handle passed to flush(). */
    public array $flushCalls = [];

    /** Every handle passed to close(). */
    public array $closeCalls = [];

    public function buildConnection(
        string|int $port_device,
        int $baud_rate = 9600,
        Parity $parity = Parity::NONE,
        StopBits $stop_bits = StopBits::ONE,
        DataBits $data_bits = DataBits::EIGHT,
        FlowControl $flow_control = FlowControl::NONE
    ): UARTTransport {
        $this->buildConnectionCalls[] = [
            'port_device' => $port_device,
            'baud_rate' => $baud_rate,
            'parity' => $parity,
            'stop_bits' => $stop_bits,
            'data_bits' => $data_bits,
            'flow_control' => $flow_control,
        ];

        return $this->buildConnectionReturnValue ?? new UART(new FakeUARTConnectionHandle, $this);
    }

    public function flush(UARTConnectionHandleInterface $handle): void
    {
        $this->flushCalls[] = $handle;
    }

    public function read(int $len, UARTConnectionHandleInterface $handle): array|false
    {
        $this->readCalls[] = [$len, $handle];

        return $this->readReturnValue;
    }

    public function write(string|array $data, UARTConnectionHandleInterface $handle): int
    {
        $this->writeCalls[] = [$data, $handle];

        return $this->writeReturnValue;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        $this->closeCalls[] = $handle;
    }
}
