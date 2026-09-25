<?php

namespace GeneralPurposeIO\I2C;

use Throwable;
use Voyager\NutsAndBolts\Collection;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use GeneralPurposeIO\NutsAndBolts\BusQueue;
use GeneralPurposeIO\NutsAndBolts\OffloadsBusJobs;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Contracts\I2C\I2CTransport;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;

abstract class I2CConnectionDriver
{
    use OffloadsBusJobs;

    public readonly Collection $connections;

    /** @var array<string, I2CTransport> "<device>:<address>" => transport */
    protected array $transports = [];

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string|int $device, int $slave_address): I2CTransport;

    abstract protected function newConnection(int|string $device): I2CConnectionFactory;

    /** Close what connectTo() opened for one bus. Its slaves are already closed. */
    abstract protected function closeConnection(mixed $handle): void;

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

    /** One transport per slave per bus; a closed one is replaced by a fresh one. */
    public function device(string|int $device, int $slave_address): ?I2CTransport
    {
        if(! $this->connections->has($device)) {
            return null;
        }

        $key = "{$device}:{$slave_address}";
        $transport = $this->transports[$key] ?? null;

        if(is_null($transport) || $transport->closed()) {
            $transport = $this->transports[$key] = $this->getTransport($device, $slave_address)->attachTo($this, $device);
        }

        return $transport;
    }

    /** Close every slave on the bus, then the bus itself. connectTo() can open it again. */
    public function disconnect(string|int $device): void
    {
        foreach ($this->transports as $key => $transport) {
            if (str_starts_with($key, "{$device}:")) {
                $transport->close();
                unset($this->transports[$key]);
            }
        }

        $this->forgetQueues($device);

        if ($this->connections->has($device)) {
            $this->closeConnection($this->connections->get($device));
            $this->connections->forget($device);
        }
    }

    /** Which queue a slave's jobs share. i2c-dev makes each transfer atomic, so slaves queue apart. */
    protected function queueKey(string|int $device, int $address): string
    {
        return "{$device}:{$address}";
    }

    /**
     * Where a job runs: a BusGig to the named work target, or to the configured one. An in-process target (sync,
     * defer) runs the gig on this process's own worker-side driver, with its own bus handle, as a worker would.
     */
    protected function dispatch(string|int $device, int $address, BusJob $job, ?string $target, Loop $loop, BusQueue $queue): Promise
    {
        return $this->runGig(new BusGig(static::class, $device, $address, $job), $target);
    }

    protected function protocolException(): string
    {
        return I2CException::class;
    }

    protected function closedReason(int $address): Throwable
    {
        return I2CException::transportClosed($address);
    }
}
