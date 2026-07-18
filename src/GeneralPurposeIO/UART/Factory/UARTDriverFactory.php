<?php

namespace GeneralPurposeIO\UART\Factory;

use GeneralPurposeIO\Contracts\UART\DataBits;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\Contracts\UART\StopBits;
use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\UART;

abstract class UARTDriverFactory
{
    public string|int|null $device = null;

    public int $baud_rate = 9600;

    public Parity $parity = Parity::NONE;

    public StopBits $stop_bits = StopBits::ONE;

    public DataBits $data_bits = DataBits::EIGHT;

    public FlowControl $flow_control = FlowControl::NONE;

    abstract public function create(): UART;

    public function device(int|string $value): static
    {
        $this->device = $value;

        return $this;
    }

    public function baud(int $value): static
    {
        $this->baud_rate = $value;

        return $this;
    }

    public function parity(Parity $value): static
    {
        $this->parity = $value;

        return $this;
    }

    public function dataBits(DataBits $value): static
    {
        $this->data_bits = $value;

        return $this;
    }

    public function stopBits(StopBits $value): static
    {
        $this->stop_bits = $value;

        return $this;
    }

    public function flowControl(FlowControl $value): static
    {
        $this->flow_control = $value;

        return $this;
    }

    /**
     * @throws UARTException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw UARTException::missingMasterDevice();
        }
    }
}
