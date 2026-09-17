<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use Voyager\NutsAndBolts\Collection;

abstract class DigitalIOConnectionDriver
{
    public readonly Collection $connections;

    protected array $pins = [];

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getOutputTransport(string|int $device, int $pin): DigitalOutputTransport;
    abstract protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): DigitalInputTransport;

    abstract protected function newConnection(int|string $device): DigitalIOConnectionFactory;

    public function connectTo(int|string $chip): DigitalIOConnectionFactory
    {
        if($this->connections->has($chip)) {
            throw new DigitalIOException("Device {$chip} already connected");
        }

        return $this->newConnection($chip);
    }

    public function register(string $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);
        return $this;
    }

    public function output(string|int $device, int $pin): ?DigitalOutputTransport
    {
        if($this->connections->has($device)) {
            return $this->getOutputTransport($device, $pin);
        }

        return null;
    }

    public function input(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): ?DigitalInputTransport
    {
        if($this->connections->has($device)) {
            return $this->getInputTransport($device, $pin, $bias, $active_low)->boundTo($device);
        }

        return null;
    }
}
