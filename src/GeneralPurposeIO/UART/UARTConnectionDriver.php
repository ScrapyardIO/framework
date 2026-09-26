<?php

namespace GeneralPurposeIO\UART;

use Closure;
use GeneralPurposeIO\Contracts\UART\UARTException;
use Voyager\NutsAndBolts\Collection;

abstract class UARTConnectionDriver
{
    public readonly Collection $connections;

    /** @var array<string, UARTTransport> device => its port */
    protected array $ports = [];

    private ?Closure $loop_resolver = null;

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string $device): UARTTransport;

    abstract protected function newConnection(string $device): UARTConnectionFactory;

    /** Gives back a connection no port was handed out for. A handed-out port gives it back itself on close(). */
    abstract protected function closeConnection(mixed $handle): void;

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

    public function connectTo(string $device): UARTConnectionFactory
    {
        $this->forgetClosed($device);

        if ($this->connections->has($device)) {
            throw UARTException::alreadyConnected($device);
        }

        return $this->newConnection($device);
    }

    /** The port on $device, one per connection. Closing it closes the connection: connectTo() opens it again. */
    public function device(string $device): ?UARTTransport
    {
        $this->forgetClosed($device);

        if (! $this->connections->has($device)) {
            return null;
        }

        return $this->ports[$device] ??= $this->attach($this->getTransport($device));
    }

    /** Closes the port, or the connection when no port was handed out. connectTo() can open it again. */
    public function disconnect(string $device): void
    {
        $this->forgetClosed($device);
        $port = $this->ports[$device] ?? null;
        unset($this->ports[$device]);

        if (! is_null($port)) {
            $port->close();
        } elseif ($this->connections->has($device)) {
            $this->closeConnection($this->connections->get($device));
        }

        $this->connections->forget($device);
    }

    /** A port looks the loop up when it needs it, so a loop bound after the driver was built still counts. */
    private function attach(UARTTransport $port): UARTTransport
    {
        return is_null($this->loop_resolver) ? $port : $port->resolvesLoopWith($this->loop_resolver);
    }

    /** A port closed on its own took its connection with it. */
    private function forgetClosed(string $device): void
    {
        if (($this->ports[$device] ?? null)?->closed()) {
            unset($this->ports[$device]);
            $this->connections->forget($device);
        }
    }
}
