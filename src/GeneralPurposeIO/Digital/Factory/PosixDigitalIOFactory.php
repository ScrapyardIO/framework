<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Digital\Bus\PosixDigitalIOBus;
use GeneralPurposeIO\Digital\Drivers\DigitalIODriver;
use GeneralPurposeIO\Digital\Drivers\PosixDigitalIODriver;

class PosixDigitalIOFactory extends DigitalIOFactory
{
    public string $consumer = "scrapyard-io-digital-io";

    public function __construct(
        protected int $device
    ) {}

    /**
     * @throws DigitalIOException
     */
    public function bus(): PosixDigitalIOBus
    {
        $driver = $this->driver();

        return new PosixDigitalIOBus($driver);
    }

    /**
     * @throws DigitalIOException
     */
    public function driver(): PosixDigitalIODriver
    {
        $this->assertReady();

        $req_config = gpiod_request_config_new();
        gpiod_request_config_set_consumer($req_config, $this->consumer);
        $chip = gpiod_chip_open("/dev/gpiochip{$this->device}");

        return new PosixDigitalIODriver($req_config, $chip);
    }

    public function consumer(string $name): static
    {
        $this->consumer = $name;

        return $this;
    }

    /**
     * @throws DigitalIOException
     */
    protected function assertReady(): void
    {
        if(!file_exists("/dev/gpiochip{$this->device}"))
        {
            throw new DigitalIOException("Device {$this->device} does not exist");
        }
    }
}