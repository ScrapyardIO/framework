<?php

namespace GeneralPurposeIO\I2C;

use Voyager\NutsAndBolts\Collection;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;

abstract class I2CConnectionDriver
{
    public readonly Collection $connections;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string|int $device, int $slave_address): I2CTransport;

    abstract protected function newConnection(int|string $device): I2CConnectionFactory;

    public function register(string $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);
        return $this;
    }

    public function connectTo(int|string $device): I2CConnectionFactory
    {
        if($this->connections->has($device)) {
            throw new I2CException("Device {$device} already connected");
        }

        return $this->newConnection($device);
    }

    public function device(string|int $device, int $slave_address): ?I2CTransport
    {
        if($this->connections->has($device)) {
            return $this->getTransport($device, $slave_address);
        }

        return null;
    }
}