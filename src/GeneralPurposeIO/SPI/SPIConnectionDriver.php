<?php

namespace GeneralPurposeIO\SPI;

use Voyager\NutsAndBolts\Collection;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPITransport;

abstract class SPIConnectionDriver
{
    public readonly Collection $connections;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(int|string $device, int $chip_select): SPITransport;

    abstract protected function newConnection(int|string $device): SPIConnectionFactory;

    public function register(string $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);
        return $this;
    }

    public function connectTo(int|string $device): SPIConnectionFactory
    {
        if($this->connections->has("$device")) {
            throw new SPIException("Device $device-* already connected");
        }

        return $this->newConnection($device);
    }

    public function device(string|int $device, int $chip_select = 0): ?SPITransport
    {
        if($this->connections->has($device)) {
            return $this->getTransport($device, $chip_select);
        }

        return null;
    }
}