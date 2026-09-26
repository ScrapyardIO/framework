<?php

namespace GeneralPurposeIO\PWM;

use Throwable;
use Voyager\NutsAndBolts\Collection;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use GeneralPurposeIO\NutsAndBolts\BusQueue;
use GeneralPurposeIO\NutsAndBolts\OffloadsBusJobs;
use GeneralPurposeIO\Contracts\PWM\PWMException;
use GeneralPurposeIO\Contracts\NutsAndBolts\BusJob;

abstract class PWMConnectionDriver
{
    use OffloadsBusJobs;

    public readonly Collection $connections;

    /** @var array<string, PWMTransport> "<chip>:<channel>" => transport */
    protected array $transports = [];

    public function __construct()
    {
        $this->connections = new Collection();
    }

    abstract protected function getTransport(string|int $device, int $channel): PWMTransport;

    abstract protected function newConnection(int|string $device): PWMConnectionFactory;

    /** Close what connectTo() opened for one chip. Its channels are already closed. */
    abstract protected function closeConnection(mixed $handle): void;

    public function register(string|int $name, mixed $handle): static
    {
        $this->connections->put($name, $handle);

        return $this;
    }

    public function connectTo(int|string $device): PWMConnectionFactory
    {
        if ($this->connections->has($device)) {
            throw PWMException::alreadyConnected($device);
        }

        return $this->newConnection($device);
    }

    /** One transport per channel per chip; a closed one is replaced by a fresh one. */
    public function device(string|int $device, int $channel): ?PWMTransport
    {
        if (! $this->connections->has($device)) {
            return null;
        }

        $key = "{$device}:{$channel}";
        $transport = $this->transports[$key] ?? null;

        if (is_null($transport) || $transport->closed()) {
            $fresh = $this->getTransport($device, $channel)->attachTo($this, $device);

            // getTransport() may wait on the loop for the channel to come up; keep one another caller stored meanwhile
            $landed = $this->transports[$key] ?? null;
            $transport = $this->transports[$key] = (! is_null($landed) && ! $landed->closed()) ? $landed : $fresh;
        }

        return $transport;
    }

    /** Close every channel on the chip, then the chip itself. connectTo() can open it again. */
    public function disconnect(string|int $device): void
    {
        foreach ($this->transports as $key => $transport) {
            if (! str_contains($key, ':')) {
                continue;
            }

            [$chip] = explode(':', $key, 2);

            if ((string) $chip !== (string) $device) {
                continue;
            }

            $transport->close();
            unset($this->transports[$key]);
        }

        $this->forgetQueues($device);

        if ($this->connections->has($device)) {
            $this->closeConnection($this->connections->get($device));
            $this->connections->forget($device);
        }
    }

    /** What a worker builds this driver with, so its jobs reach the same hardware. Scalars only: they cross a pipe. */
    public function workerArguments(): array
    {
        return [];
    }

    /** Each channel queues apart: the kernel serialises every apply, so channels never need to wait on each other. */
    protected function queueKey(string|int $device, int $channel): string
    {
        return "{$device}:{$channel}";
    }

    /**
     * A PWMChannelGig to the named work target, or to the configured one. An in-process target (sync, defer) runs it
     * on a worker-side driver built from the same arguments, as a worker would.
     */
    protected function dispatch(string|int $device, int $channel, BusJob $job, ?string $target, Loop $loop, BusQueue $queue): Promise
    {
        return $this->runGig(new PWMChannelGig(static::class, $this->workerArguments(), $device, $channel, $job), $target);
    }

    protected function protocolException(): string
    {
        return PWMException::class;
    }

    protected function closedReason(int $channel): Throwable
    {
        return PWMException::transportClosed($channel);
    }
}
