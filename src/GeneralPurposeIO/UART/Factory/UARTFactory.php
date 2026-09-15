<?php

namespace GeneralPurposeIO\UART\Factory;

use GeneralPurposeIO\Contracts\UART\DataBits;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\Contracts\UART\StopBits;
use GeneralPurposeIO\Contracts\UART\UARTDriver;

abstract class UARTFactory
{
    public int $baud_rate = 9_600;

    public Parity $parity = Parity::NONE;

    public StopBits $stop_bits = StopBits::ONE;

    public DataBits $data_bits = DataBits::EIGHT;

    public FlowControl $flow_control = FlowControl::NONE;

    abstract protected function assertReady(): void;

    abstract public function driver(): UARTDriver;

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
}
