<?php

namespace Waveforms\Carriers\I2C\API\Native\Factory;

use Exception;
use Waveforms\Carriers\I2C\API\Native\Exceptions\NativeI2CException;
use Waveforms\Carriers\I2C\API\Native\NativeI2CDevice;
use Waveforms\Carriers\I2C\Factory\I2CConnectionBuilder;

class NativeI2CConnectionBuilder extends I2CConnectionBuilder
{
    private string $device_path = '/dev/i2c-';

    public ?int $master = null;

    /**
     * @throws Exception
     */
    public function firstly(int|string $chip_device): static
    {
        if (is_string($chip_device)) {
            throw new Exception(static::class.' requires the master to be an int.');
        }

        return $this->master($chip_device);
    }

    public function master(int $master): static
    {
        $this->master = $master;

        return $this;
    }

    public function connection(): string
    {
        return 'native';
    }

    public function boot(): NativeI2CDevice
    {
        if (is_null($this->master)) {
            throw NativeI2CException::missingMaster();
        }

        if (is_null($this->slave_address)) {
            throw NativeI2CException::missingSlaveAddress();
        }

        $i2c_bus = i2c_open("{$this->device_path}{$this->master}", $this->slave_address);

        if (! is_null($i2c_bus)) {
            return new NativeI2CDevice($i2c_bus);
        }

        throw NativeI2CException::couldNotOpenI2CDevice("{$this->device_path}{$this->master}@{$this->slave_address}");
    }
}
