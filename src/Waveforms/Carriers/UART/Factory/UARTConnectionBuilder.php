<?php

namespace Waveforms\Carriers\UART\Factory;

use Waveforms\Carriers\UART\Enums\DataBits;
use Waveforms\Carriers\UART\Enums\FlowControl;
use Waveforms\Carriers\UART\Enums\Parity;
use Waveforms\Carriers\UART\Enums\StopBits;
use Waveforms\Carriers\UART\UARTDevice;
use Waveforms\Factory\CarrierFactory;

abstract class UARTConnectionBuilder extends CarrierFactory
{
    public int $baud_rate = 9600;

    public DataBits $data_bits = DataBits::EIGHT;

    public Parity $parity = Parity::NONE;

    public StopBits $stop_bits = StopBits::ONE;

    public FlowControl $flow_control = FlowControl::NONE;

    abstract public function boot(): UARTDevice;

    public function baud(int $rate): static
    {
        $this->baud_rate = $rate;

        return $this;
    }

    public function dataBits(DataBits $bits): static
    {
        $this->data_bits = $bits;

        return $this;
    }

    public function parity(Parity $parity): static
    {
        $this->parity = $parity;

        return $this;
    }

    public function stopBits(StopBits $stop_bits): static
    {
        $this->stop_bits = $stop_bits;

        return $this;
    }

    public function flowControl(FlowControl $flow_control): static
    {
        $this->flow_control = $flow_control;

        return $this;
    }
}
