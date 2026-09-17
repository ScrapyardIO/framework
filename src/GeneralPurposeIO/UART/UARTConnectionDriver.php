<?php

namespace GeneralPurposeIO\UART;

use GeneralPurposeIO\Contracts\UART\UARTException;
use Voyager\NutsAndBolts\Collection;
use GeneralPurposeIO\Contracts\UART\UARTTransport;

abstract class UARTConnectionDriver
{
    public readonly Collection $connections;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string $device): UARTTransport;

    abstract protected function newConnection(string $device): UARTConnectionFactory;

    public function register(string $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);
        return $this;
    }

    public function connectTo(string $device): UARTConnectionFactory
    {
        if($this->connections->has($device)) {
            throw new UARTException("Device $device already connected");
        }

        return $this->newConnection($device);
    }

    public function device(string|int $device): ?UARTTransport
    {
        if($this->connections->has($device)) {
            return $this->getTransport($device);
        }

        return null;
    }
}