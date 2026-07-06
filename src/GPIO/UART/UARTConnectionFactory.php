<?php

namespace GPIO\UART;

use GPIO\Common\GPIOConnectionFactory;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\UART\DataBits;
use GPIO\Contracts\UART\FlowControl;
use GPIO\Contracts\UART\Parity;
use GPIO\Contracts\UART\StopBits;
use GPIO\Contracts\UART\UARTDriverAdapter as UARTDriverAdapterInterface;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\UART\UARTConnectionFactory as UARTConnectionFactoryContract;
use GPIO\Contracts\UART\UARTException;

#[CarrierFactory('uart')]
class UARTConnectionFactory extends GPIOConnectionFactory implements UARTConnectionFactoryContract
{
    /** @var UARTDriverAdapterInterface  */
    protected GPIODriverAdapterInterface $driver_adapter;

    public string|int|null $master_port_device = null;

    public int $baud_rate = 9600;

    public Parity $parity = Parity::NONE;

    public StopBits $stop_bits = StopBits::ONE;

    public DataBits $data_bits = DataBits::EIGHT;

    public FlowControl $flow_control = FlowControl::NONE;

    public function __construct(
        string $driver
    ) {
        parent::__construct($driver);
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

    public function device(int|string $value): static
    {
        $this->master_port_device = $value;
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
    public function create(): UART
    {
        if(is_null($this->master_port_device)) {
            throw UARTException::missingMasterDevice();
        }

        return $this->driver_adapter->buildConnection(
            $this->master_port_device,
            $this->baud_rate,
            $this->parity,
            $this->stop_bits,
            $this->data_bits,
            $this->flow_control,
        );
    }
}
