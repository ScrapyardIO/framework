<?php

namespace GPIO\Contracts\UART;

use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;

interface UARTConnectionFactory extends GPIOConnectionFactoryContract
{
    public function baud(int $value): static;
    public function parity(Parity $value): static;
    public function device(int|string $value): static;
    public function dataBits(DataBits $value): static;
    public function stopBits(StopBits $value): static;
    public function flowControl(FlowControl $value): static;
}
