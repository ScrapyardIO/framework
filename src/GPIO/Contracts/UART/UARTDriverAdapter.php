<?php

namespace GPIO\Contracts\UART;

use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterContract;
use GPIO\Contracts\UART\UARTConnectionHandle as UARTConnectionHandleInterface;

interface UARTDriverAdapter extends GPIODriverAdapterContract
{
    public function buildConnection(
        string|int $port_device,
        int $baud_rate = 9600,
        Parity $parity = Parity::NONE,
        StopBits $stop_bits = StopBits::ONE,
        DataBits $data_bits = DataBits::EIGHT,
        FlowControl $flow_control = FlowControl::NONE
    ): UARTTransport;

    public function flush(UARTConnectionHandleInterface $handle): void;
    public function read(int $len, UARTConnectionHandleInterface $handle): array|false;
    public function write(string|array $data, UARTConnectionHandleInterface $handle): int;
}
