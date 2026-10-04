<?php

namespace GeneralPurposeIO\Digital;

use Closure;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\DigitalOutTransport;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use Voyager\NutsAndBolts\Collection;

abstract class DigitalIOConnectionDriver
{
    public readonly Collection $connections;

    /** @var array<string, DigitalInTransport|DigitalOutTransport> "<device>:<pin>" => transport */
    protected array $pins = [];

    private ?Closure $loop_resolver = null;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function newConnection(int|string $device): DigitalIOConnectionFactory;
    abstract protected function getOutputTransport(string|int $device, int $pin): DigitalOutTransport;
    abstract protected function getInputTransport(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): DigitalInTransport;

    /** Close what connectTo() opened for one device. Its pins are already closed. */
    abstract protected function closeConnection(mixed $handle): void;

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

    /** Wire-internal: the manager hands every driver the closure that finds the event loop. */
    public function resolvesLoopWith(Closure $resolver): static
    {
        $this->loop_resolver = $resolver;
        return $this;
    }

    public function output(string|int $device, int $pin): ?DigitalOutTransport
    {
        if($this->connections->has($device)) {
            $this->forgetClosed($device, $pin);
            return $this->getOutputTransport($device, $pin);
        }

        return null;
    }

    public function input(string|int $device, int $pin, LineBias $bias = LineBias::AS_IS, bool $active_low = false): ?DigitalInTransport
    {
        if($this->connections->has($device))
        {
            $this->forgetClosed($device, $pin);
            $input = $this->getInputTransport($device, $pin, $bias, $active_low)->boundTo($device);

            return is_null($this->loop_resolver) ? $input : $input->resolvesLoopWith($this->loop_resolver);
        }

        return null;
    }

    /** Close every pin on the device, then the device itself. connectTo() can open it again. */
    public function disconnect(string|int $device): void
    {
        foreach ($this->pins as $key => $pin) {
            if (str_starts_with($key, "{$device}:"))
            {
                $pin->close();
                unset($this->pins[$key]);
            }
        }

        if ($this->connections->has($device))
        {
            $this->closeConnection($this->connections->get($device));
            $this->connections->forget($device);
        }
    }

    /** A closed pin leaves the cache, so the next input()/output() on it requests it fresh. */
    private function forgetClosed(string|int $device, int $pin): void
    {
        if (($this->pins["{$device}:{$pin}"] ?? null)?->closed())
        {
            unset($this->pins["{$device}:{$pin}"]);
        }
    }
}