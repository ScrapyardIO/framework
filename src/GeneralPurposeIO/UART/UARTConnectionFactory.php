<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\Contracts\UART\DataBits;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\Contracts\UART\StopBits;

abstract class UARTConnectionFactory
{
    public int $baud_rate = 9_600;

    public Parity $parity = Parity::NONE;

    public StopBits $stop_bits = StopBits::ONE;

    public DataBits $data_bits = DataBits::EIGHT;

    public FlowControl $flow_control = FlowControl::NONE;

    public function __construct(
        public string $device,
        protected UARTConnectionDriver $driver
    ) {}

    abstract protected function device(): mixed;
    abstract protected function getHandle(): mixed;

    public function baud(int $value): static
    {
        $this->baud_rate = $value;

        return $this;
    }

    public function parity(Parity|int $value): static
    {
        $this->parity = is_int($value) ? Parity::from($value) : $value;

        return $this;
    }

    public function stopBits(StopBits|int $value): static
    {
        $this->stop_bits = is_int($value) ? StopBits::from($value) : $value;

        return $this;
    }

    public function dataBits(DataBits|int $value): static
    {
        $this->data_bits = is_int($value) ? DataBits::from($value) : $value;

        return $this;
    }

    public function flowControl(FlowControl|int $value): static
    {
        $this->flow_control = is_int($value) ? FlowControl::from($value) : $value;

        return $this;
    }

    public function register(): UARTConnectionDriver
    {
        return $this->driver->register($this->device, $this->getHandle());
    }
}