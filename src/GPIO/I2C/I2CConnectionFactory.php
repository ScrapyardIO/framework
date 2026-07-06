<?php

namespace GPIO\I2C;

use GPIO\Contracts\I2C\I2CException;
use GPIO\Common\GPIOConnectionFactory;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Digital\DigitalPinsOnBus;
use GPIO\Contracts\I2C\I2CDriverAdapter as I2CDriverAdapterInterface;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\I2C\I2CConnectionFactory as I2CConnectionFactoryContract;


#[CarrierFactory('i2c')]
class I2CConnectionFactory extends GPIOConnectionFactory implements I2CConnectionFactoryContract
{
    use DigitalPinsOnBus;

    /** @var I2CDriverAdapterInterface  */
    protected GPIODriverAdapterInterface $driver_adapter;

    public ?int $slave_address = null;

    public string|int|null $master_host_device = null;

    public function __construct(
        string $driver
    ) {
        parent::__construct($driver);
    }

    public function device(int|string $value): static
    {
        $this->master_host_device = $value;
        return $this;
    }

    /**
     * @throws I2CException
     */
    public function slave(int $value): static
    {
        if(($value > 0x07) && ($value <= 0x77))
        {
            $this->slave_address  = $value;
            return $this;
        }

        throw I2CException::invalidSlaveAddress($value);
    }

    /**
     * @throws I2CException
     */
    public function create(): I2C|I2CConnectionBus
    {
        if(is_null($this->master_host_device)) {
            throw I2CException::missingMasterDevice();
        }

        if(is_null($this->slave_address)) {
            throw I2CException::missingSlaveAddress();
        }

        return $this->driver_adapter->buildConnection(
            $this->master_host_device,
            $this->slave_address,
            $this->gpio_chip,
            $this->digital_pins
        );
    }
}
