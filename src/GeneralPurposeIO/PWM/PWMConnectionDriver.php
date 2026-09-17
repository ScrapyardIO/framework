<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\PWM\PWMTransport;
use Voyager\NutsAndBolts\Collection;

abstract class PWMConnectionDriver
{
    public readonly Collection $connections;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string|int $device, int $channel): PWMTransport;

    abstract protected function newConnection(int|string $device): PWMConnectionFactory;

    public function register(string|int $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);
        return $this;
    }

    public function connectTo(int|string $device): PWMConnectionFactory
    {
        if($this->connections->has($device)) {
            throw new PWMException("Device {$device} already connected");
        }

        return $this->newConnection($device);
    }

    public function device(string|int $device, int $channel): ?PWMTransport
    {
        if($this->connections->has($device)) {
            return $this->getTransport($device, $channel);
        }

        return null;
    }
}
