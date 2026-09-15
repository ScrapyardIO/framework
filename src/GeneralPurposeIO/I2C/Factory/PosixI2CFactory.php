<?php

namespace GeneralPurposeIO\I2C\Factory;

use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\I2C\Bus\PosixI2CBus;
use GeneralPurposeIO\I2C\Drivers\PosixI2CDriver;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;
use Microscrap\Bindings\POSIX\Enums\FileControlFlag;

class PosixI2CFactory extends I2CFactory
{
    public function __construct(
        protected int $device
    ) {}

    /**
     * @throws I2CException
     */
    public function bus(): PosixI2CBus
    {
        $driver = $this->driver();

        return new PosixI2CBus($driver);
    }

    /**
     * @throws I2CException
     */
    public function driver(): PosixI2CDriver
    {
        $this->assertReady();
        $path = "/dev/i2c-{$this->device}";
        $fd = posix_open($path, FileControlFlag::O_RDWR->value);

        if($fd < 0)
        {
            throw new I2CException("POSIX I2C handle for [i2c-{$this->device}] could not be opened.");
        }

        return new PosixI2CDriver($fd);
    }

    /**
     * @throws I2CException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw I2CException::missingMasterDevice();
        }
    }
}